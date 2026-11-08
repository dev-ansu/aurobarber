<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ativação do Sistema de Licença
    |--------------------------------------------------------------------------
    | Se desativado, o sistema ignora a verificação remota (ideal para testes
    | e desenvolvimento local). Em produção, defina como true.
    */
    'enabled' => (bool) env('LICENSE_CHECK_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Chave de Identificação da Escola
    |--------------------------------------------------------------------------
    | Identificador exclusivo da escola (ex: CNPJ ou código único).
    | Este valor será enviado como parâmetro para o webhook do Google Sheets.
    */
    'key' => env('LICENSE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | URL do Webhook do Google Apps Script
    |--------------------------------------------------------------------------
    | A URL da aplicação web gerada no Google Sheets que responde com o JSON
    | contendo { ativo: true/false, vencimento: "...", mensagem: "..." }.
    */
    'webhook_url' => env('LICENSE_WEBHOOK_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Dias de Carência Offline
    |--------------------------------------------------------------------------
    | Quantidade de dias que o sistema pode continuar rodando sem internet
    | caso não consiga se comunicar com o Google Sheets.
    */
    'grace_days' => (int) env('LICENSE_GRACE_DAYS', 5),

    /*
    |--------------------------------------------------------------------------
    | Duração do Cache em Horas
    |--------------------------------------------------------------------------
    | Para evitar requisições desnecessárias a cada clique, o resultado fica
    | em cache por este período de tempo.
    */
    'cache_hours' => (int) env('LICENSE_CACHE_HOURS', 12),

    /*
    |--------------------------------------------------------------------------
    | Contato de Suporte Padrão (WhatsApp)
    |--------------------------------------------------------------------------
    | Telefone que aparecerá na tela de bloqueio caso a planilha não envie outro.
    */
    'support_phone' => env('LICENSE_SUPPORT_PHONE', ''),

    /*
    |--------------------------------------------------------------------------
    | Servidores de Validação com Redundância Automática (Cascata de Contingência)
    |--------------------------------------------------------------------------
    | Ordem automática de checagem. Se a máquina principal de casa (ansu)
    | estiver desligada, tenta o PC do trabalho (pc-13), e por fim a planilha
    | do Google Sheets na nuvem.
    */
    'verification_servers' => [
        'http://ansu.tailfd86d3.ts.net:8000/api/v1/license/verify',
        'http://pc-13.tailfd86d3.ts.net:8000/api/v1/license/verify',
        'https://script.google.com/macros/s/AKfycbw-ComMVkrhDLc7_V8Ntu7OdrcDS8Cd4-PzREUgXP7gpdJ7WPsXytdixUVKdPz9CzaJ/exec',
    ],
];

