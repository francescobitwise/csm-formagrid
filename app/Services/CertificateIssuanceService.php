<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Tenant\Certificate;
use App\Models\Tenant\Enrollment;
use App\Support\MediaStorage;
use App\Support\TenantPdfLogo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class CertificateIssuanceService
{
    /**
     * Crea o aggiorna il certificato PDF per un’iscrizione completata.
     */
    public function ensureIssued(Enrollment $enrollment): ?Certificate
    {
        if ($enrollment->status !== EnrollmentStatus::Completed) {
            return null;
        }

        $enrollment->loadMissing(['user', 'course']);

        if ($enrollment->user === null || $enrollment->course === null) {
            return null;
        }

        $instanceId = 'single';

        $certificate = Certificate::query()->where('enrollment_id', $enrollment->id)->first();

        if ($certificate === null) {
            $certificate = Certificate::query()->create([
                'enrollment_id' => $enrollment->id,
                'user_id' => $enrollment->user_id,
                'course_id' => $enrollment->course_id,
                'pdf_path' => null,
                'issued_at' => $enrollment->completed_at ?? now(),
            ]);
        } else {
            $certificate->forceFill([
                'user_id' => $enrollment->user_id,
                'course_id' => $enrollment->course_id,
                'issued_at' => $certificate->issued_at ?? $enrollment->completed_at ?? now(),
            ])->save();
        }

        $disk = MediaStorage::disk();
        $relativePath = 'tenants/'.$instanceId.'/certificates/'.$certificate->id.'.pdf';

        if (Storage::disk($disk)->exists($relativePath)) {
            if ($certificate->pdf_path !== $relativePath) {
                $certificate->forceFill(['pdf_path' => $relativePath])->save();
            }

            return $certificate->fresh();
        }

        $pdf = $this->buildPdf($enrollment, $certificate);
        $binary = $pdf->output();

        Storage::disk($disk)->put(
            $relativePath,
            $binary,
            MediaStorage::putOptionsForDisk($disk),
        );

        $certificate->forceFill([
            'pdf_path' => $relativePath,
            'issued_at' => $certificate->issued_at ?? $enrollment->completed_at ?? now(),
        ])->save();

        return $certificate->fresh();
    }

    private function buildPdf(Enrollment $enrollment, Certificate $certificate): mixed
    {
        $orgName = (string) config('app.name', 'Organizzazione');
        $accent = '#1a6dbf';
        // Single-client fork: accent/theme is configured globally (no per-tenant settings).

        return Pdf::loadView('tenant.learner.certificate-pdf', [
            'certificate' => $certificate,
            'enrollment' => $enrollment,
            'learnerName' => (string) $enrollment->user->name,
            'courseTitle' => (string) $enrollment->course->title,
            'completedAt' => $enrollment->completed_at ?? $certificate->issued_at ?? now(),
            'tenantDisplayName' => $orgName,
            'logoDataUri' => TenantPdfLogo::dataUri(),
            'accent' => $accent,
            'certificateReference' => Str::upper(Str::substr(str_replace('-', '', (string) $certificate->id), 0, 12)),
        ])->setPaper('a4', 'landscape');
    }
}
