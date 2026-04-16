<?php

declare(strict_types=1);

return [
    // Google Analytics 4 measurement id, es: G-XXXXXXXXXX
    'ga4_measurement_id' => env('GA4_MEASUREMENT_ID'),

    /*
    | GA4 opzionale sui domini tenant. Se valorizzato, banner consenso come sulla centrale.
    */
    'tenant_ga4_measurement_id' => env('TENANT_GA4_MEASUREMENT_ID', ''),

    // Tra due eventi watch-time: oltre questa pausa (secondi) si apre una nuova "sessione".
    'watch_time_session_gap_seconds' => (int) env('WATCH_TIME_SESSION_GAP_SECONDS', 1800),
];
