<?php

namespace App\Enums;

/**
 * Permessi area admin tenant. Estendere (es. reports.view, billing.view) quando le funzioni saranno pronte.
 */
enum TenantPermission: string
{
    case AdminDashboard = 'admin.dashboard';

    /** Lista corsi (sola lettura) */
    case ContentCoursesRead = 'content.courses.read';
    /** Creazione / modifica / eliminazione corsi e builder */
    case ContentCoursesManage = 'content.courses.manage';

    /** Lista moduli e accesso alla pagina lezioni del modulo */
    case ContentModulesRead = 'content.modules.read';
    /** Creazione / modifica / eliminazione moduli */
    case ContentModulesManage = 'content.modules.manage';

    /** CRUD lezioni, upload SCORM/documenti, retry job */
    case ContentLessons = 'content.lessons';

    /** Presigned upload video (API) */
    case ContentMediaUpload = 'content.media.upload';

    case LearnersManage = 'learners.manage';
    case CompaniesManage = 'companies.manage';
    case SettingsTenant = 'settings.tenant';
    case StaffManage = 'staff.manage';

    case ReportsView = 'reports.view';

    /** Registro attività staff (solo admin di default). */
    case AuditLogView = 'audit.view';

    /** Hub compliance, export portability, registro richieste interessati. */
    case ComplianceManage = 'compliance.manage';
}
