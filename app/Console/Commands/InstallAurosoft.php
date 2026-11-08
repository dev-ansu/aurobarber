<?php

namespace App\Console\Commands;

use App\Models\Person;
use App\Models\User;
use App\Services\CourseSyncService;
use App\Services\OuroModernoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class InstallAurosoft extends Command
{
    protected $signature = 'app:install-aurosoft
        {--skip-lms-sync : Não sincroniza cursos da Ouro Moderno durante a instalação}';

    protected $description = 'Instala o Aurosoft sem criar dados fictícios e configura o primeiro acesso administrativo.';

    public function handle(OuroModernoService $ouroModerno, CourseSyncService $courseSync): int
    {
        $this->components->info('Preparando a instalação do Aurosoft');

        try {
            DB::connection()->getPdo();
        } catch (Throwable $exception) {
            $this->components->error('Não foi possível conectar ao banco de dados configurado. Confira DB_CONNECTION e as credenciais no .env.');
            report($exception);

            return self::FAILURE;
        }

        $needsInitialAdmin = ! Schema::hasTable('users') || ! DB::table('users')->exists();
        $admin = $needsInitialAdmin ? $this->collectInitialAdminDetails() : null;

        if ($needsInitialAdmin && $admin === null) {
            return self::FAILURE;
        }

        if (blank(config('app.key'))) {
            if ($this->call('key:generate', ['--force' => true]) !== self::SUCCESS) {
                $this->components->error('Não foi possível gerar a chave da aplicação.');

                return self::FAILURE;
            }

            if (is_file(base_path('bootstrap/cache/config.php')) && $this->call('config:clear') !== self::SUCCESS) {
                $this->components->error('A chave foi gerada, mas não foi possível limpar o cache de configuração.');

                return self::FAILURE;
            }
        } else {
            $this->components->twoColumnDetail('Chave da aplicação', 'já configurada; mantida');
        }

        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            $this->components->error('Uma ou mais migrations falharam. A instalação foi interrompida.');

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Banco de dados', 'migrations aplicadas');

        if (! $this->ensurePublicStorageLink()) {
            return self::FAILURE;
        }

        if ($this->call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'permissions',
        ]) !== self::SUCCESS) {
            $this->components->error('Não foi possível gerar as permissões do painel.');

            return self::FAILURE;
        }

        try {
            $this->provisionRolesAndInitialAdmin($admin);
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('As migrations foram aplicadas, mas não foi possível configurar os papéis ou o usuário inicial: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($needsInitialAdmin) {
            $this->components->twoColumnDetail('Administrador inicial', $admin['email']);
        } else {
            $this->components->warn('Já existem usuários. Nenhuma conta ou senha existente foi alterada.');
        }

        if (! $this->option('skip-lms-sync') && $ouroModerno->isConfigured()) {
            $this->components->info('Sincronizando o catálogo da Ouro Moderno...');

            try {
                $count = $courseSync->syncCourses();
                $this->components->twoColumnDetail('Cursos sincronizados', (string) $count);
            } catch (Throwable $exception) {
                report($exception);
                $this->components->warn('A instalação local foi concluída, mas a sincronização LMS falhou. Você pode tentar depois com php artisan app:sync-ouro-moderno-courses.');
            }
        } elseif (! $this->option('skip-lms-sync')) {
            $this->components->twoColumnDetail('Integração Ouro Moderno', 'não configurada; ignorada');
        } else {
            $this->components->twoColumnDetail('Integração Ouro Moderno', 'ignorada por opção');
        }

        $this->newLine();
        $this->components->success('Aurosoft instalado sem dados fictícios.');

        if ($needsInitialAdmin) {
            $this->line('Acesse o painel administrativo com o e-mail informado e a senha definida nesta instalação.');
        }

        return self::SUCCESS;
    }

    /** @return array{name: string, email: string, password: string}|null */
    private function collectInitialAdminDetails(): ?array
    {
        if (! $this->input->isInteractive()) {
            $this->components->error('O banco não tem usuários e o comando está em modo não interativo. Execute-o em um terminal para definir o primeiro administrador.');

            return null;
        }

        $name = trim((string) $this->ask('Nome do administrador'));
        $email = trim((string) $this->ask('E-mail de acesso'));
        $password = (string) $this->secret('Senha (mínimo de 12 caracteres)');
        $passwordConfirmation = (string) $this->secret('Confirme a senha');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(12)],
        ], [
            'password.confirmed' => 'As senhas informadas não coincidem.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return null;
        }

        return [
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ];
    }

    /** @param array{name: string, email: string, password: string}|null $admin */
    private function provisionRolesAndInitialAdmin(?array $admin): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function () use ($admin): void {
            $secretaria = Role::findOrCreate('secretaria', 'web');
            $professor = Role::findOrCreate('professor', 'web');
            $agendaPermission = Permission::findOrCreate('View:Agenda', 'web');
            $secretaria->givePermissionTo($agendaPermission);
            $professor->givePermissionTo($agendaPermission);

            $superAdmin = Role::findOrCreate('super_admin', 'web');
            $superAdmin->syncPermissions(Permission::query()->where('guard_name', 'web')->get());

            if ($admin === null || User::query()->exists()) {
                return;
            }

            Validator::make($admin, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:people,email', 'unique:users,email'],
                'password' => ['required', Password::min(12)],
            ])->validate();

            $person = Person::query()->create([
                'name' => $admin['name'],
                'email' => $admin['email'],
            ]);

            $user = User::query()->create([
                'person_id' => $person->id,
                'email' => $admin['email'],
                'password' => Hash::make($admin['password']),
            ]);

            $user->assignRole($superAdmin);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function ensurePublicStorageLink(): bool
    {
        $link = public_path('storage');
        $target = storage_path('app/public');
        File::ensureDirectoryExists($target);

        if (is_link($link)) {
            if (realpath($link) === realpath($target)) {
                $this->components->twoColumnDetail('Arquivos públicos', 'link já configurado');

                return true;
            }

            $this->components->error('public/storage já aponta para outro local. O instalador não irá substituí-lo.');

            return false;
        }

        if (file_exists($link)) {
            $this->components->error('public/storage já existe e não é um link simbólico. Revise-o manualmente antes de continuar.');

            return false;
        }

        if ($this->call('storage:link') !== self::SUCCESS || ! is_link($link) || realpath($link) !== realpath($target)) {
            $this->components->error('Não foi possível configurar o link público dos arquivos enviados.');

            return false;
        }

        $this->components->twoColumnDetail('Arquivos públicos', 'link configurado');

        return true;
    }
}
