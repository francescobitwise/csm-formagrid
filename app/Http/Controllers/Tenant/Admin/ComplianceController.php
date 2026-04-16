<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Admin;

use App\Enums\PrivacyRequestStatus;
use App\Enums\PrivacyRequestType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\PrivacyContactRequest;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

final class ComplianceController extends Controller
{
    public function index(): View
    {
        $requests = PrivacyContactRequest::query()
            ->with('recordedBy')
            ->orderByDesc('created_at')
            ->limit(80)
            ->get();

        return view('tenant.admin.compliance.index', [
            'requests' => $requests,
            'requestTypes' => PrivacyRequestType::cases(),
            'privacyStatuses' => PrivacyRequestStatus::cases(),
        ]);
    }

    public function exportPortability(): BinaryFileResponse|RedirectResponse
    {
        if (! class_exists(ZipArchive::class)) {
            return back()->withErrors(['export' => 'Estensione ZIP non disponibile sul server.']);
        }

        $learners = User::query()
            ->where('role', UserRole::Learner)
            ->orderBy('email')
            ->get(['id', 'name', 'email', 'created_at', 'email_verified_at']);

        $learnersCsv = $this->csvFromRows(
            ['id', 'name', 'email', 'created_at', 'email_verified_at'],
            $learners->map(fn (User $u) => [
                $u->id,
                $u->name,
                $u->email,
                $u->created_at?->toIso8601String(),
                $u->email_verified_at?->toIso8601String(),
            ])->all(),
        );

        $enrollments = Enrollment::query()
            ->with(['user:id,name,email', 'course:id,title,slug'])
            ->orderBy('enrolled_at')
            ->get();

        $enrollmentRows = $enrollments->map(function (Enrollment $e) {
            return [
                $e->id,
                $e->user_id,
                $e->user?->email,
                $e->user?->name,
                $e->course_id,
                $e->course?->title,
                $e->course?->slug,
                $e->status->value,
                (string) $e->progress_pct,
                $e->enrolled_at?->toIso8601String(),
                $e->completed_at?->toIso8601String(),
            ];
        })->all();

        $enrollCsv = $this->csvFromRows(
            ['enrollment_id', 'user_id', 'user_email', 'user_name', 'course_id', 'course_title', 'course_slug', 'status', 'progress_pct', 'enrolled_at', 'completed_at'],
            $enrollmentRows,
        );

        $tmp = tempnam(sys_get_temp_dir(), 'fg-export-');
        if ($tmp === false) {
            return back()->withErrors(['export' => 'Impossibile creare un file temporaneo.']);
        }

        $zipPath = $tmp.'.zip';
        if (! @unlink($tmp)) {
            // tempnam created a file; remove before zip path
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->withErrors(['export' => 'Impossibile creare l’archivio ZIP.']);
        }

        $zip->addFromString('learners.csv', $learnersCsv);
        $zip->addFromString('enrollments.csv', $enrollCsv);
        $zip->close();

        $tenantId = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string) tenant('id')) ?: 'tenant';

        return response()->download($zipPath, 'portability-'.$tenantId.'-'.now()->format('Y-m-d').'.zip')
            ->deleteFileAfterSend(true);
    }

    public function storePrivacyRequest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contact_email' => ['required', 'email', 'max:255'],
            'request_type' => ['required', Rule::enum(PrivacyRequestType::class)],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
        ]);

        PrivacyContactRequest::query()->create([
            'recorded_by_user_id' => $request->user()?->getKey(),
            'contact_email' => $data['contact_email'],
            'request_type' => $data['request_type'],
            'message' => $data['message'],
            'status' => PrivacyRequestStatus::New,
            'status_updated_at' => now(),
        ]);

        return back()->with('toast', 'Richiesta registrata nel registro interno.');
    }

    public function updatePrivacyRequestStatus(Request $request, PrivacyContactRequest $privacyContactRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(PrivacyRequestStatus::class)],
        ]);

        $privacyContactRequest->status = $data['status'];
        $privacyContactRequest->status_updated_at = now();
        $privacyContactRequest->save();

        return back()->with('toast', 'Stato richiesta aggiornato.');
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<int, scalar|null>>  $rows
     */
    private function csvFromRows(array $headers, array $rows): string
    {
        $fh = fopen('php://temp', 'r+');
        if ($fh === false) {
            return '';
        }

        fputcsv($fh, $headers, ';');
        foreach ($rows as $row) {
            fputcsv($fh, $row, ';');
        }
        rewind($fh);
        $content = stream_get_contents($fh);
        fclose($fh);

        return $content ?: '';
    }
}
