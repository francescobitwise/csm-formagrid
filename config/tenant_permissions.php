<?php

use App\Enums\TenantPermission;
use App\Enums\UserRole;

/**
 * Permessi per ruolo (tenant). Admin: tutto. Istruttore: solo contenuti (lezioni / upload), niente allievi, profilo tenant, staff, report, billing.
 */
return [
    UserRole::Admin->value => ['*'],

    UserRole::Instructor->value => [
        TenantPermission::AdminDashboard->value,
        TenantPermission::ContentCoursesRead->value,
        TenantPermission::ContentModulesRead->value,
        TenantPermission::ContentLessons->value,
        TenantPermission::ContentMediaUpload->value,
    ],
];
