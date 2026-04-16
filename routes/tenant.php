<?php

declare(strict_types=1);

use App\Enums\TenantPermission;
use App\Http\Controllers\Tenant\Admin\ComplianceController;
use App\Http\Controllers\Tenant\Admin\CompanyController;
use App\Http\Controllers\Tenant\Admin\CourseBuilderController;
use App\Http\Controllers\Tenant\Admin\CourseController;
use App\Http\Controllers\Tenant\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Tenant\Admin\LearnerController;
use App\Http\Controllers\Tenant\Admin\ModuleController;
use App\Http\Controllers\Tenant\Admin\ModuleLessonController;
use App\Http\Controllers\Tenant\Admin\StaffAuditLogController;
use App\Http\Controllers\Tenant\Admin\StaffController;
use App\Http\Controllers\Tenant\Admin\TenantProfileController;
use App\Http\Controllers\Tenant\AuthController;
use App\Http\Controllers\Tenant\ForgotPasswordController;
use App\Http\Controllers\Tenant\Learner\CertificateController;
use App\Http\Controllers\Tenant\Learner\CourseController as LearnerCourseController;
use App\Http\Controllers\Tenant\Learner\DashboardController;
use App\Http\Controllers\Tenant\Learner\HlsManifestController;
use App\Http\Controllers\Tenant\Learner\LessonController as LearnerLessonController;
use App\Http\Controllers\Tenant\Learner\ScormContentController;
use App\Http\Controllers\Tenant\RequiredPasswordController;
use App\Http\Controllers\Tenant\ResetPasswordController;
use App\Http\Middleware\LogTenantStaffAudit;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    'tenant.must_change_password',
])->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'create'])->name('tenant.login');
        Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:12,1')->name('tenant.login.store');

        Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('tenant.password.request');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:6,1')->name('tenant.password.email');

        Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('tenant.password.reset');
        Route::post('/reset-password', [ResetPasswordController::class, 'store'])->middleware('throttle:8,1')->name('tenant.password.update');
    });

    Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('tenant.logout');

    Route::middleware('auth')->group(function () {
        Route::get('/password/required', [RequiredPasswordController::class, 'edit'])->name('tenant.password.required');
        Route::post('/password/required', [RequiredPasswordController::class, 'update'])
            ->middleware('throttle:12,1')
            ->name('tenant.password.required.update');
    });

    // Home: single-client, private app (no product landing / self-registration).
    Route::get('/', function () {
        if (Auth::check()) {
            return redirect()->route('tenant.dashboard');
        }

        return redirect()->route('tenant.login');
    })->name('tenant.home');
    Route::get('/dashboard', DashboardController::class)->middleware('auth')->name('tenant.dashboard');
    Route::get('/courses', [LearnerCourseController::class, 'index'])->middleware('auth')->name('tenant.courses.index');
    Route::post('/courses/{course}/enroll', [LearnerCourseController::class, 'enroll'])->middleware('auth')->name('tenant.courses.enroll');
    Route::get('/courses/{course}', [LearnerCourseController::class, 'show'])->middleware('auth')->name('tenant.courses.show');
    Route::get('/courses/{course}/certificate', [CertificateController::class, 'download'])
        ->middleware(['auth', 'throttle:30,1'])
        ->name('tenant.courses.certificate');
    Route::get('/courses/{course}/lessons/{lesson}/hls-manifest.m3u8', HlsManifestController::class)
        ->middleware(['auth', 'throttle:120,1'])
        ->name('tenant.learner.hls.manifest');
    Route::get('/courses/{course}/lessons/{lesson}', [LearnerLessonController::class, 'show'])->middleware('auth')->name('tenant.lessons.show');
    Route::get('/scorm/{package}/{path?}', ScormContentController::class)
        ->where('path', '.*')
        ->middleware('auth')
        ->name('tenant.scorm.asset');

    // Admin tenant (staff: admin o istruttore; permessi granulari sulle rotte)
    Route::prefix('admin')->middleware(['auth', 'tenant.staff', LogTenantStaffAudit::class])->group(function () {
        Route::get('/', AdminDashboardController::class)
            ->middleware('tenant.permission:'.TenantPermission::AdminDashboard->value)
            ->name('tenant.admin.dashboard');

        Route::middleware('tenant.permission:'.TenantPermission::ReportsView->value)->group(function () {
            Route::get('reports/dashboard-snapshot', [AdminDashboardController::class, 'exportSnapshot'])
                ->middleware('throttle:30,1')
                ->name('tenant.admin.dashboard.export');
        });

        Route::middleware('tenant.permission:'.TenantPermission::StaffManage->value)->group(function () {
            Route::get('staff', [StaffController::class, 'index'])->name('tenant.admin.staff.index');
            Route::get('staff/create', [StaffController::class, 'create'])->name('tenant.admin.staff.create');
            Route::post('staff', [StaffController::class, 'store'])->name('tenant.admin.staff.store');
            Route::post('staff/{user}/send-credentials', [StaffController::class, 'sendCredentials'])->name('tenant.admin.staff.send-credentials');
            Route::delete('staff/{user}', [StaffController::class, 'destroy'])->name('tenant.admin.staff.destroy');
        });

        Route::middleware('tenant.permission:'.TenantPermission::SettingsTenant->value)->group(function () {
            Route::get('profile', [TenantProfileController::class, 'edit'])->name('tenant.admin.profile.edit');
            Route::put('profile', [TenantProfileController::class, 'update'])->name('tenant.admin.profile.update');
            Route::put('profile/logo', [TenantProfileController::class, 'updateLogo'])->name('tenant.admin.profile.logo.update');
        });

        Route::permanentRedirect('branding', 'profile');

        Route::middleware('tenant.permission:'.TenantPermission::AuditLogView->value)->group(function () {
            Route::get('audit-log', [StaffAuditLogController::class, 'index'])->name('tenant.admin.audit-log.index');
        });

        Route::middleware('tenant.permission:'.TenantPermission::ComplianceManage->value)->group(function () {
            Route::get('compliance', [ComplianceController::class, 'index'])->name('tenant.admin.compliance.index');
            Route::post('compliance/export', [ComplianceController::class, 'exportPortability'])
                ->middleware('throttle:6,1')
                ->name('tenant.admin.compliance.export');
            Route::post('compliance/privacy-requests', [ComplianceController::class, 'storePrivacyRequest'])
                ->middleware('throttle:24,1')
                ->name('tenant.admin.compliance.privacy-requests.store');
            Route::patch('compliance/privacy-requests/{privacyContactRequest}', [ComplianceController::class, 'updatePrivacyRequestStatus'])
                ->middleware('throttle:60,1')
                ->name('tenant.admin.compliance.privacy-requests.update');
        });

        Route::middleware('tenant.permission:'.TenantPermission::LearnersManage->value)->group(function () {
            Route::get('learners', [LearnerController::class, 'index'])->name('tenant.admin.learners.index');
            Route::get('learners/create', [LearnerController::class, 'create'])->name('tenant.admin.learners.create');
            Route::post('learners', [LearnerController::class, 'store'])->name('tenant.admin.learners.store');
            Route::get('learners/import', [LearnerController::class, 'importForm'])->name('tenant.admin.learners.import');
            Route::post('learners/import', [LearnerController::class, 'importStore'])->middleware('throttle:6,1')->name('tenant.admin.learners.import.store');
            Route::post('learners/send-credentials-bulk', [LearnerController::class, 'sendCredentialsBulk'])->middleware('throttle:12,1')->name('tenant.admin.learners.send-credentials-bulk');
            Route::post('learners/{user}/send-credentials', [LearnerController::class, 'sendCredentials'])->name('tenant.admin.learners.send-credentials');
            Route::delete('learners/{user}', [LearnerController::class, 'destroy'])->name('tenant.admin.learners.destroy');
        });

        Route::middleware('tenant.permission:'.TenantPermission::CompaniesManage->value)->group(function () {
            Route::get('companies', [CompanyController::class, 'index'])->name('tenant.admin.companies.index');
            Route::get('companies/create', [CompanyController::class, 'create'])->name('tenant.admin.companies.create');
            Route::post('companies', [CompanyController::class, 'store'])->name('tenant.admin.companies.store');
            Route::get('companies/{company}', [CompanyController::class, 'show'])->name('tenant.admin.companies.show');
            Route::get('companies/{company}/edit', [CompanyController::class, 'edit'])->name('tenant.admin.companies.edit');
            Route::put('companies/{company}', [CompanyController::class, 'update'])->name('tenant.admin.companies.update');
            Route::delete('companies/{company}', [CompanyController::class, 'destroy'])->name('tenant.admin.companies.destroy');
        });

        Route::middleware('tenant.permission:'.TenantPermission::ContentCoursesRead->value)->group(function () {
            Route::get('courses', [CourseController::class, 'index'])->name('tenant.admin.courses.index');
            Route::get('courses/{course}/learners', [CourseController::class, 'learners'])->name('tenant.admin.courses.learners');
            Route::get('courses/{course}/companies-report', [CourseController::class, 'companiesReport'])->name('tenant.admin.courses.companies-report');
            Route::get('courses/{course}/companies-report.csv', [CourseController::class, 'companiesReportCsv'])
                ->middleware('throttle:30,1')
                ->name('tenant.admin.courses.companies-report.csv');
            Route::get('courses/{course}/learners.pdf', [CourseController::class, 'learnersPdf'])->name('tenant.admin.courses.learners.pdf');
            Route::get('courses/{course}/learners/{enrollment}/time', [CourseController::class, 'learnerTime'])
                ->name('tenant.admin.courses.learners.time');
        });

        Route::middleware('tenant.permission:'.TenantPermission::ContentCoursesManage->value)->group(function () {
            Route::get('courses/create', [CourseController::class, 'create'])->name('tenant.admin.courses.create');
            Route::post('courses', [CourseController::class, 'store'])->name('tenant.admin.courses.store');
            Route::get('courses/{course}/edit', [CourseController::class, 'edit'])->name('tenant.admin.courses.edit');
            Route::put('courses/{course}', [CourseController::class, 'update'])->name('tenant.admin.courses.update');
            Route::patch('courses/{course}', [CourseController::class, 'update']);
            Route::delete('courses/{course}', [CourseController::class, 'destroy'])->name('tenant.admin.courses.destroy');

            Route::get('courses/{course}/builder', [CourseBuilderController::class, 'show'])->name('tenant.admin.courses.builder');
            Route::post('courses/{course}/modules', [CourseBuilderController::class, 'attachModule'])->name('tenant.admin.courses.modules.store');
            Route::put('courses/{course}/modules/{module}', [CourseBuilderController::class, 'updateModule'])->name('tenant.admin.courses.modules.update');
            Route::delete('courses/{course}/modules/{module}', [CourseBuilderController::class, 'destroyModule'])->name('tenant.admin.courses.modules.destroy');
            Route::post('courses/{course}/modules/{module}/move/{direction}', [CourseBuilderController::class, 'moveModule'])->name('tenant.admin.courses.modules.move');

            Route::put('courses/{course}/learners/{enrollment}/time/sessions/{session}', [CourseController::class, 'updateLearnerTimeSession'])
                ->name('tenant.admin.courses.learners.time.sessions.update');
        });

        // Learners are managed within companies (single-client).
        Route::middleware('tenant.permission:'.TenantPermission::LearnersManage->value)->group(function () {
            Route::get('companies/{company}/learners', [LearnerController::class, 'indexForCompany'])->name('tenant.admin.companies.learners.index');
            Route::get('companies/{company}/learners/create', [LearnerController::class, 'createForCompany'])->name('tenant.admin.companies.learners.create');
            Route::post('companies/{company}/learners', [LearnerController::class, 'storeForCompany'])->name('tenant.admin.companies.learners.store');
            Route::get('companies/{company}/learners/import', [LearnerController::class, 'importFormForCompany'])->name('tenant.admin.companies.learners.import');
            Route::post('companies/{company}/learners/import', [LearnerController::class, 'importStoreForCompany'])
                ->middleware('throttle:6,1')
                ->name('tenant.admin.companies.learners.import.store');
        });

        Route::middleware('tenant.permission:'.TenantPermission::ContentModulesRead->value)->group(function () {
            Route::get('modules', [ModuleController::class, 'index'])->name('tenant.admin.modules.index');
        });

        Route::middleware('tenant.permission:'.TenantPermission::ContentModulesManage->value)->group(function () {
            Route::get('modules/create', [ModuleController::class, 'create'])->name('tenant.admin.modules.create');
            Route::post('modules', [ModuleController::class, 'store'])->name('tenant.admin.modules.store');
            Route::get('modules/{module}/edit', [ModuleController::class, 'edit'])->name('tenant.admin.modules.edit');
            Route::put('modules/{module}', [ModuleController::class, 'update'])->name('tenant.admin.modules.update');
            Route::delete('modules/{module}', [ModuleController::class, 'destroy'])->name('tenant.admin.modules.destroy');
        });

        Route::middleware('tenant.permission:'.TenantPermission::ContentLessons->value)->group(function () {
            Route::get('modules/{module}/lessons/content-status', [ModuleLessonController::class, 'contentStatus'])->name('tenant.admin.modules.lessons.content-status');
            Route::get('modules/{module}/lessons', [ModuleLessonController::class, 'show'])->name('tenant.admin.modules.lessons');
            Route::post('modules/{module}/lessons', [ModuleLessonController::class, 'storeLesson'])->name('tenant.admin.modules.lessons.store');
            Route::put('modules/{module}/lessons/{lesson}', [ModuleLessonController::class, 'updateLesson'])->name('tenant.admin.modules.lessons.update');
            Route::put('modules/{module}/lessons/{lesson}/video', [ModuleLessonController::class, 'updateVideoContent'])->name('tenant.admin.modules.lessons.video.update');
            Route::post('modules/{module}/lessons/{lesson}/video/retry', [ModuleLessonController::class, 'retryVideoProcessing'])->name('tenant.admin.modules.lessons.video.retry');
            Route::put('modules/{module}/lessons/{lesson}/scorm', [ModuleLessonController::class, 'updateScormContent'])->name('tenant.admin.modules.lessons.scorm.update');
            Route::post('modules/{module}/lessons/{lesson}/scorm/upload', [ModuleLessonController::class, 'uploadScormContent'])->name('tenant.admin.modules.lessons.scorm.upload');
            Route::post('modules/{module}/lessons/{lesson}/scorm/retry', [ModuleLessonController::class, 'retryScormProcessing'])->name('tenant.admin.modules.lessons.scorm.retry');
            Route::post('modules/{module}/lessons/{lesson}/document/upload', [ModuleLessonController::class, 'uploadDocumentContent'])->name('tenant.admin.modules.lessons.document.upload');
            Route::delete('modules/{module}/lessons/{lesson}', [ModuleLessonController::class, 'destroyLesson'])->name('tenant.admin.modules.lessons.destroy');
            Route::post('modules/{module}/lessons/{lesson}/move/{direction}', [ModuleLessonController::class, 'moveLesson'])->name('tenant.admin.modules.lessons.move');
        });
    });
});
