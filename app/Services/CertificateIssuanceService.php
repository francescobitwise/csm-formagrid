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

        $tenantId = (string) tenant('id');
        if ($tenantId === '') {
            return null;
        }

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
        $relativePath = 'tenants/'.$tenantId.'/certificates/'.$certificate->id.'.pdf';

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
        $tenant = tenant();
        $orgName = trim((string) ($tenant?->organization_name ?? ''));
        if ($orgName === '') {
            $orgName = (string) ($tenant?->id ?? config('app.name', 'Organizzazione'));
        }

        $accent = '#1a6dbf';
        $pdfSettings = is_array($tenant?->pdf_course_report) ? $tenant->pdf_course_report : [];
        $accentCandidate = (string) ($pdfSettings['accent'] ?? '');
        if (preg_match('/^#[0-9a-f]{6}$/i', $accentCandidate)) {
            $accent = $accentCandidate;
        }

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
