<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DW PEC (atenção básica) — PostgreSQL somente leitura
    |--------------------------------------------------------------------------
    */
    'pec' => [
        'host' => env('PEC_DW_HOST', '192.168.76.69'),
        'port' => (int) env('PEC_DW_PORT', 5433),
        'database' => env('PEC_DW_DATABASE', 'esus'),
        'username' => env('PEC_DW_USERNAME', 'esus_leitura'),
        'password' => env('PEC_DW_PASSWORD', ''),
        'historico_limite' => (int) env('PEC_HISTORICO_LIMITE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Policlínicas / e-SUS Mesquita — MySQL
    |--------------------------------------------------------------------------
    */
    'policlinica' => [
        'host' => env('POLI_DB_HOST', '135.119.133.36'),
        'port' => (int) env('POLI_DB_PORT', 3306),
        'database' => env('POLI_DB_DATABASE', 'esus_mesquita'),
        'username' => env('POLI_DB_USERNAME', 'semus_esus'),
        'password' => env('POLI_DB_PASSWORD', ''),
        'fallback_host' => env('POLI_DB_FALLBACK_HOST', '135.119.133.36'),
        'fallback_username' => env('POLI_DB_FALLBACK_USERNAME', 'hugo.bezerra'),
        'fallback_password' => env('POLI_DB_FALLBACK_PASSWORD', ''),
        'view' => 'vw_paciente_historico_agendamentos',
        'connect_timeout' => 5,
    ],

    'cache' => [
        'meses_padrao' => 12,
        'meses_opcoes' => [3, 6, 12, 24, 36, 60, 120],
    ],

];
