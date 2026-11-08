<?php

namespace App\Console\Commands;

use App\Services\OuroModernoService;
use Illuminate\Console\Command;
use Throwable;

class TestOuroModerno extends Command
{
    protected $signature = 'app:test-ouro-moderno';

    protected $description = 'Testa a conexão com a API da Ouro Moderno.';

    public function handle(OuroModernoService $ouroModernoService): int
    {
        $this->info('Testando a conexão com a API da Ouro Moderno...');

        try {
            $courses = $ouroModernoService->courses();

            if ($courses === []) {
                $this->warn('Nenhum curso encontrado na API da Ouro Moderno.');

                return self::SUCCESS;
            }

            $this->info('Conexão realizada; a API retornou dados de cursos.');
            $this->line('Use app:sync-ouro-moderno-courses para importar o catálogo.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Falha na integração: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
