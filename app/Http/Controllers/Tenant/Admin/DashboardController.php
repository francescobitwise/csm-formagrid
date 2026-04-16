<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Admin;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Certificate;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\User;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('tenant.admin.dashboard', [
            'stats' => $this->stats(),
        ]);
    }

    public function exportSnapshot(): StreamedResponse
    {
        $stats = $this->stats();
        $tenantId = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string) tenant('id')) ?: 'tenant';

        return response()->streamDownload(function () use ($stats): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['metrica', 'valore'], ';');
            foreach ($stats as $label => $value) {
                fputcsv($out, [(string) $label, (string) $value], ';');
            }
            fclose($out);
        }, 'riepilogo-'.$tenantId.'-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, string|int>
     */
    private function stats(): array
    {
        $learners = User::query()->where('role', UserRole::Learner)->count();
        $staff = User::query()->whereIn('role', [UserRole::Admin, UserRole::Instructor])->count();

        return [
            'generato_il' => now()->timezone(config('app.timezone'))->toIso8601String(),
            'tenant_id' => (string) tenant('id'),
            'corsi_pubblicati' => Course::query()->where('status', CourseStatus::Published)->count(),
            'corsi_bozza' => Course::query()->where('status', CourseStatus::Draft)->count(),
            'corsi_archiviati' => Course::query()->where('status', CourseStatus::Archived)->count(),
            'allievi' => $learners,
            'staff' => $staff,
            'iscrizioni_totali' => Enrollment::query()->count(),
            'iscrizioni_attive' => Enrollment::query()->where('status', EnrollmentStatus::Active)->count(),
            'iscrizioni_completate' => Enrollment::query()->where('status', EnrollmentStatus::Completed)->count(),
            'certificati_emessi' => Certificate::query()->count(),
        ];
    }
}
