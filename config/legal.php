<?php

declare(strict_types=1);

return [
    'company_name' => env('LEGAL_COMPANY_NAME', env('APP_NAME', 'FormaGrid')),
    'address' => env('LEGAL_COMPANY_ADDRESS', ''),
    'vat' => env('LEGAL_VAT', ''),
    'email' => env('LEGAL_EMAIL', env('MAIL_FROM_ADDRESS', 'info@formagrid.it')),
    'privacy_email' => env('LEGAL_PRIVACY_EMAIL', env('LEGAL_EMAIL', env('MAIL_FROM_ADDRESS', 'privacy@example.com'))),
    'effective_date' => env('LEGAL_EFFECTIVE_DATE', '2026-04-06'),
    'subprocessors' => [
        [
            'vendor' => 'Stripe',
            'service' => 'Pagamenti, fatture, Customer Portal',
            'data' => 'Dati di fatturazione e identificativi cliente',
        ],
        [
            'vendor' => 'Hosting/Cloud provider',
            'service' => 'Esecuzione applicazione, database, backup',
            'data' => 'Dati LMS e log tecnici',
        ],
        [
            'vendor' => 'Object storage (S3/R2)',
            'service' => 'Media: video HLS, SCORM, documenti',
            'data' => 'File caricati dal Cliente e metadati',
        ],
        [
            'vendor' => 'Email provider',
            'service' => 'Invio credenziali e notifiche',
            'data' => 'Email e contenuti dei messaggi',
        ],
        [
            'vendor' => 'Analytics (opzionale)',
            'service' => 'Misurazione uso del sito (previo consenso)',
            'data' => 'Eventi/identificativi tecnici',
        ],
    ],
];

