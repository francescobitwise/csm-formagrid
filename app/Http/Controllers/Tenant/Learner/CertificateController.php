<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Learner;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Services\CertificateIssuanceService;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CertificateController extends Controller
{
    public function download(Request $request, Course $course, CertificateIssuanceService $issuer): StreamedResponse
    {
        abort_unless($course->status === CourseStatus::Published, 404);

        $enrollment = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('user_id', $request->user()->id)
            ->where('status', EnrollmentStatus::Completed)
            ->firstOrFail();

        $certificate = $issuer->ensureIssued($enrollment);
        if ($certificate === null || ! is_string($certificate->pdf_path) || $certificate->pdf_path === '') {
            abort(404);
        }

        $safeSlug = preg_replace('/[^a-z0-9_-]+/i', '-', $course->slug) ?: 'corso';

        return Storage::disk(MediaStorage::disk())->download(
            $certificate->pdf_path,
            'certificato-'.$safeSlug.'.pdf',
        );
    }
}
