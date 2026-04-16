<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Backup database → object storage
    |--------------------------------------------------------------------------
    |
    | Dump MySQL (landlord + ogni DB tenant) con mysqldump, gzip, upload su S3.
    | Richiede mysqldump nel PATH sul server (o BACKUP_MYSQLDUMP_PATH).
    | Abilita in produzione: BACKUP_DATABASE_ENABLED=true
    |
    */

    'database_enabled' => env('BACKUP_DATABASE_ENABLED', false),

    'disk' => env('BACKUP_S3_DISK', 's3_backups'),

    /** Prefisso chiavi oggetto (senza slash iniziale/finale). */
    's3_prefix' => trim((string) env('BACKUP_S3_PREFIX', 'backups/database'), '/'),

    /** Eseguibile mysqldump (PATH o percorso assoluto). */
    'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),

    /** Timeout secondi per ogni singolo dump (0 = illimitato). */
    'dump_timeout' => (int) env('BACKUP_DUMP_TIMEOUT', 0),

];
