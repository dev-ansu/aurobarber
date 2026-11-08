<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ManageDeveloperUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aurosoft:developer
                            {--email= : E-mail da conta oculta de desenvolvedor}
                            {--password= : Senha de acesso do desenvolvedor}
                            {--list : Listar contas de desenvolvedores registradas}
                            {--delete= : Excluir uma conta de desenvolvedor pelo e-mail}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gerencia contas ocultas de desenvolvedor com privilégios técnicos de diagnóstico';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listDevelopers();
        }

        if ($deleteEmail = $this->option('delete')) {
            return $this->deleteDeveloper((string) $deleteEmail);
        }

        return $this->createOrUpdateDeveloper();
    }

    private function listDevelopers(): int
    {
        $developers = User::query()
            ->where('is_developer', true)
            ->get(['id', 'email', 'created_at', 'last_seen_at']);

        if ($developers->isEmpty()) {
            $this->warn('Nenhuma conta de desenvolvedor registrada no momento.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'E-mail', 'Criado em', 'Último Acesso'],
            $developers->map(fn (User $dev) => [
                $dev->id,
                $dev->email,
                $dev->created_at?->format('d/m/Y H:i:s') ?? 'N/A',
                $dev->last_seen_at?->format('d/m/Y H:i:s') ?? 'Nunca',
            ])
        );

        return self::SUCCESS;
    }

    private function deleteDeveloper(string $email): int
    {
        $dev = User::query()
            ->where('is_developer', true)
            ->where('email', $email)
            ->first();

        if (! $dev) {
            $this->error("Conta de desenvolvedor com e-mail [{$email}] não encontrada.");

            return self::FAILURE;
        }

        $dev->delete();
        $this->info("Conta de desenvolvedor [{$email}] removida com sucesso.");

        return self::SUCCESS;
    }

    private function createOrUpdateDeveloper(): int
    {
        $email = $this->option('email')
            ?: config('app.developer_email')
            ?: env('DEVELOPER_EMAIL');

        if (empty($email)) {
            $email = $this->ask('Informe o e-mail do Desenvolvedor', 'dev@aurosoft.internal');
        }

        $password = $this->option('password')
            ?: config('app.developer_password')
            ?: env('DEVELOPER_PASSWORD');

        if (empty($password)) {
            $password = $this->secret('Informe a senha do Desenvolvedor (mínimo 8 caracteres com letras e números)')
                ?: Str::password(16);
        }

        if (strlen((string) $password) < 8) {
            $this->error('A senha deve ter no mínimo 8 caracteres.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user && ! $user->is_developer) {
            $this->error("O e-mail [{$email}] já pertence a um usuário regular do sistema.");

            return self::FAILURE;
        }

        if (! $user) {
            $user = new User;
            $user->email = $email;
        }

        // Garante que o desenvolvedor tenha uma entidade técnica Person isolada sem vínculos escolares
        $person = \App\Models\Person::withoutGlobalScopes()->firstOrCreate(
            ['email' => $email],
            ['name' => 'Desenvolvedor Aurosoft']
        );
        $user->person_id = $person->id;

        $user->password = Hash::make((string) $password);
        $user->is_developer = true;
        $user->panel_access = true;
        $user->save();

        $this->newLine();
        $this->info('====================================================');
        $this->info('  CONTA DE DESENVOLVEDOR CONFIGURADA COM SUCESSO!  ');
        $this->info('====================================================');
        $this->line("E-mail:    <fg=cyan>{$email}</>");
        $this->line('Acesso:    <fg=green>Totalmente Oculta (Isolada dos dados da escola)</>');
        $this->line('Portal:    <fg=yellow>/developer/login</>');
        $this->info('====================================================');
        $this->newLine();

        return self::SUCCESS;
    }
}
