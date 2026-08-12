<?php

use App\Enums\TenantPermission;
use App\Enums\UserRole;

/**
 * Permessi per ruolo (tenant). Admin: tutto. Istruttore: solo contenuti (lezioni / upload).
 * Ispettore: dashboard + lettura report corsi assegnati (niente manage).
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

    UserRole::Inspector->value => [
        TenantPermission::AdminDashboard->value,
        TenantPermission::ContentCoursesRead->value,
    ],
];
