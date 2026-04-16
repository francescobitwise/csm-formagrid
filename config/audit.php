<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Retention registro attività staff (tenant)
    |--------------------------------------------------------------------------
    |
    | Le righe più vecchie di N giorni vengono eliminate dal comando schedulato
    | `tenants:prune-staff-audit-logs`. Impostare 0 per disattivare la cancellazione
    | (il comando non elimina nulla).
    |
    */
    'staff_log_retention_days' => (int) env('STAFF_AUDIT_LOG_RETENTION_DAYS', 365),

];
