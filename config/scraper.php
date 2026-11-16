<?php

return [
    'base_url' => env('SEAGRI_BASE_URL', 'https://www.ba.gov.br/seagri/cotacao'),

    // Produto: 37 = Cacau (até 15:30h)
    'produto' => env('SEAGRI_PRODUTO_ID', '37'),

    // Tipo: 484 = (comum)
    'tipo' => env('SEAGRI_TIPO_ID', '484'),

    // Praças em destaque exibidas nos cards principais do painel (demais cidades da Bahia
    // são descobertas automaticamente via <select name="praca"> da SEAGRI).
    'cities' => [
        229 => 'Ilhéus',
        242 => 'Itabuna',
        467 => 'Uruçuca',
        407 => 'Santa Luzia',
    ],

    // Nomes com acentuação amigável para praças conhecidas
    'city_labels' => [
        229 => 'Ilhéus',
        242 => 'Itabuna',
        467 => 'Uruçuca',
        407 => 'Santa Luzia',
        201 => 'Gandu',
        213 => 'Ibicaraí',
        219 => 'Ibirapitanga',
        221 => 'Ibirataia',
        232 => 'Ipiaú',
        243 => 'Itacaré',
        250 => 'Itajuípe',
        269 => 'Ituberá',
        400 => 'Salvador',
        461 => 'Ubaitaba',
        462 => 'Ubatã',
        465 => 'Una',
        469 => 'Valença',
        477 => 'Vitória da Conquista',
    ],

    // Intervalo padrão quando executado sem --from/--to (em dias retroativos)
    'default_lookback_days' => (int) env('SCRAPER_DEFAULT_LOOKBACK_DAYS', 30),

    // Limites e proteção anti-DDoS
    'timeout' => (int) env('SCRAPER_TIMEOUT', 30),
    'retries' => (int) env('SCRAPER_RETRIES', 3),
    'delay_min_ms' => (int) env('SCRAPER_DELAY_MIN_MS', 2000),
    'delay_max_ms' => (int) env('SCRAPER_DELAY_MAX_MS', 3500),
    'max_pages' => (int) env('SCRAPER_MAX_PAGES', 100),

    'user_agent' => env(
        'SCRAPER_USER_AGENT',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ),
];
