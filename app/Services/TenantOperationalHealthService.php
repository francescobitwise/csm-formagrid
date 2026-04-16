<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProcessingStatus;
use App\Enums\UserRole;
use App\Models\Tenant\ScormPackage;
use App\Models\Tenant\User;
use App\Models\Tenant\VideoLesson;
use Illuminate\Support\Facades\DB;

/**
 * Snapshot operativo del tenant corrente (chiamare solo dentro tenancy inizializzata).
 *
 * @return array{
 *     level: 'ok'|'warn'|'error',
 *     summary: string,
 *     metrics: array<string, int|string|null|bool>
 * }
 */
final class TenantOperationalHealthService
{
    public function snapshot(): array
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            return [
                'level' => 'error',
                'summary' => 'Database non raggiungibile',
                'metrics' => ['db_error' => $e->getMessage()],
            ];
        }

        $learners = User::query()->where('role', UserRole::Learner)->count();
        $staff = User::query()->whereIn('role', [UserRole::Admin, UserRole::Instructor])->count();

        $videoErrors = VideoLesson::query()->where('status', ProcessingStatus::Error)->count();
        $videoProcessing = VideoLesson::query()->where('status', ProcessingStatus::Processing)->count();
        $scormErrors = ScormPackage::query()->where('status', ProcessingStatus::Error)->count();
        $scormProcessing = ScormPackage::query()->where('status', ProcessingStatus::Processing)->count();

        [$mediaBytes, $mediaKnown] = app(MediaStorageUsageService::class)->measureCurrentTenantMediaBytes();

        $metrics = [
            'learners' => $learners,
            'staff' => $staff,
            'video_errors' => $videoErrors,
            'video_processing' => $videoProcessing,
            'scorm_errors' => $scormErrors,
            'scorm_processing' => $scormProcessing,
            'media_bytes' => $mediaBytes,
            'media_known' => $mediaKnown,
        ];

        $hasMediaErrors = $videoErrors > 0 || $scormErrors > 0;
        $hasProcessing = $videoProcessing > 0 || $scormProcessing > 0;

        $level = 'ok';
        if ($hasMediaErrors) {
            $level = 'warn';
        } elseif ($hasProcessing) {
            $level = 'warn';
        }

        if ($hasMediaErrors) {
            $chunks = [];
            if ($videoErrors > 0) {
                $chunks[] = "video in errore: {$videoErrors}";
            }
            if ($scormErrors > 0) {
                $chunks[] = "SCORM in errore: {$scormErrors}";
            }
            $summary = implode(', ', $chunks);
        } elseif ($hasProcessing) {
            $summary = 'Processing contenuti in corso';
        } else {
            $summary = 'Nessun problema rilevato';
        }

        return [
            'level' => $level,
            'summary' => $summary,
            'metrics' => $metrics,
        ];
    }
}
