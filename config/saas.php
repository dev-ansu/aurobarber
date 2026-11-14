<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | SaaS Master Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Chave compartilhada entre o painel SaaS Master (Aurosoft) e o Aurobarber
    | para autenticação mútua de comandos de licenciamento, provisionamento e backup.
    |
    */
    'webhook_secret' => env('SAAS_WEBHOOK_SECRET', 'aurobarber-saas-secret-change-in-production'),

    /*
    |--------------------------------------------------------------------------
    | URL do Painel Central SaaS Master (Aurosoft)
    |--------------------------------------------------------------------------
    */
    'master_url' => env('SAAS_MASTER_URL', 'https://aurosoft.com.br'),
];
