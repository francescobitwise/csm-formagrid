<?php

declare(strict_types=1);

$base = env('CENTRAL_PUBLIC_BASE');
if (! is_string($base) || $base === '') {
    $scheme = parse_url((string) env('APP_URL', 'https://localhost'), PHP_URL_SCHEME) ?: 'https';
    $host = (string) env('CENTRAL_DOMAIN', config('app.central_domain', 'localhost'));
    $base = $scheme.'://'.$host;
}

return [
    'central_base_url' => rtrim($base, '/'),

    'document_paths' => [
        'privacy' => '/privacy',
        'cookies' => '/cookie',
        'terms' => '/termini',
        'dpa' => '/dpa',
        'subprocessors' => '/sub-responsabili',
    ],
];
