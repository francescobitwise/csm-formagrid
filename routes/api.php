<?php

use App\Enums\TenantPermission;
use App\Http\Controllers\Api\ScormTrackingController;
use App\Http\Controllers\Api\VideoDirectUploadController;
use App\Http\Controllers\Api\VideoProgressController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    // We keep CSRF support for browser-based tracking calls.
    'web',
    'auth',
    'tenant.must_change_password',
])->group(function () {
    Route::get('/health', fn () => ['ok' => true]);

    Route::put('/scorm/track', [ScormTrackingController::class, 'update'])
        ->middleware('throttle:120,1');
    Route::get('/scorm/status', [ScormTrackingController::class, 'status'])
        ->middleware('throttle:120,1');
    Route::put('/video/progress', [VideoProgressController::class, 'update'])
        ->middleware('throttle:90,1');
    Route::get('/video/status', [VideoProgressController::class, 'status'])
        ->middleware('throttle:120,1');

    Route::middleware(['tenant.staff', 'tenant.permission:'.TenantPermission::ContentMediaUpload->value])->group(function () {
        Route::post('/video/presigned-upload', [VideoDirectUploadController::class, 'presign'])
            ->name('api.video.presigned-upload');
        Route::post('/video/finalize-upload', [VideoDirectUploadController::class, 'finalize'])
            ->name('api.video.finalize-upload');
    });
});
