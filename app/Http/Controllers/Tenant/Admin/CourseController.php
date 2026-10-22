<?php

namespace App\Http\Controllers\Tenant\Admin;

use App\Enums\CourseStatus;
use App\Enums\TenantPermission;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Company;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\Lesson;
use App\Models\Tenant\User;
use App\Services\CourseAutoEnrollmentService;
use App\Services\SessionLessonProgressService;
use App\Services\TenantQuotaService;
use App\Services\WatchTimeSessionImportService;
use App\Services\WatchTimeSessionService;
use App\Support\MediaStorage;
use App\Support\TenantPdfLogo;
use App\Support\TenantPermissions;
use App\Support\UploadedFileStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        /** @var User|null $user */
        $user = $request->user();

        $canManageCourses = TenantPermissions::allows($user, TenantPermission::ContentCoursesManage);

        $courses = Course::query()
            ->when($user?->isInspector(), function ($query) use ($user) {
                $query->whereHas('inspectors', fn ($q) => $q->whereKey($user->id));
            })
            ->when($user?->isCompanyManager(), function ($query) use ($user) {
                $companyId = $user->company_id;
                if ($companyId === null) {
                    $query->whereRaw('0 = 1');

                    return;
                }

                $query->whereHas('enrollments.user', fn ($learners) => $learners->where('company_id', $companyId));
            })
            ->when($q !== '', fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('tenant.admin.courses.index', [
            'courses' => $courses,
            'q' => $q,
            'status' => $status,
            'statuses' => CourseStatus::cases(),
            'createCompanies' => $canManageCourses ? Company::query()->orderBy('name')->get() : collect(),
            'createLearners' => $canManageCourses
                ? User::query()->where('role', UserRole::Learner)->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function learners(Request $request, Course $course)
    {
        $this->assertCourseReadable($request, $course);

        $activeWithinSeconds = 90;
        $activeCutoff = now()->subSeconds($activeWithinSeconds);

        $enrollments = $this->enrollmentReportBaseQuery($course, includeLastActivity: true)
            ->orderByDesc(DB::raw('GREATEST(COALESCE(vagg.last_video_sync_at, 0), COALESCE(sagg.last_scorm_sync_at, 0))'))
            ->paginate(25)
            ->withQueryString();

        $this->decorateEnrollmentsForReport($enrollments->getCollection(), $activeCutoff);

        return view('tenant.admin.courses.learners', [
            'course' => $course,
            'enrollments' => $enrollments,
            'activeWithinSeconds' => $activeWithinSeconds,
        ]);
    }

    public function companiesReport(Request $request, Course $course): View
    {
        $this->assertCourseReadable($request, $course);

        $rows = $this->companyAggregatesForCourse($course)->get();

        $totalSecondsAll = (int) $rows->sum(fn ($r) => (int) ($r->total_seconds ?? 0));

        return view('tenant.admin.courses.companies-report', [
            'course' => $course,
            'rows' => $rows,
            'totalSecondsAll' => $totalSecondsAll,
        ]);
    }

    public function companiesReportCsv(Request $request, Course $course): Response
    {
        $this->assertCourseReadable($request, $course);

        $rows = $this->companyAggregatesForCourse($course)->get();

        $filename = 'report-aziende-'.$course->slug.'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $handle = fopen('php://temp', 'wb+');
        if ($handle === false) {
            abort(500, 'Impossibile creare il CSV.');
        }

        // UTF-8 BOM for Excel
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Azienda', 'Corsisti', 'Iscrizioni completate', 'Ore totali'], ';');

        foreach ($rows as $r) {
            $name = (string) ($r->company_name ?: 'Senza azienda');
            $seconds = (int) ($r->total_seconds ?? 0);
            $hours = $seconds / 3600;
            fputcsv($handle, [
                $name,
                (int) ($r->learners_count ?? 0),
                (int) ($r->completed_enrollments ?? 0),
                number_format($hours, 2, ',', '.'),
            ], ';');
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response($content === false ? '' : $content, 200, $headers);
    }

    public function learnersPdf(Request $request, Course $course): Response
    {
        $this->assertCourseReadable($request, $course);

        $rows = $this->enrollmentReportBaseQuery($course, includeLastActivity: false)
            ->orderBy('enrollments.created_at')
            ->get();

        /** @var Tenant|null $tenant */
        $tenant = tenant();
        $pdfData = (is_array($tenant?->pdf_course_report) ? $tenant->pdf_course_report : []);

        $accent = (string) ($pdfData['accent'] ?? '');
        $accent = preg_match('/^#[0-9a-f]{6}$/i', $accent) ? $accent : '#f59e0b';

        $header = (string) ($pdfData['header'] ?? '');
        $footer = (string) ($pdfData['footer'] ?? '');

        $logoDataUri = TenantPdfLogo::dataUri();
        $viewer = $request->user();

        $pdf = Pdf::loadView('tenant.admin.courses.learners-pdf', [
            'course' => $course,
            'rows' => $rows,
            'audienceNote' => $viewer instanceof User && $viewer->isCompanyManager()
                ? 'Solo gli allievi della tua azienda.'
                : null,
            'tenantName' => (string) ($tenant?->organization_name ?? $tenant?->id ?? ''),
            'accent' => $accent,
            'headerText' => $header,
            'footerText' => $footer,
            'logoDataUri' => $logoDataUri,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $filename = 'resoconto-ore-corso-'.$course->slug.'.pdf';

        return $pdf->download($filename);
    }

    public function learnerReportPdf(Request $request, Course $course, Enrollment $enrollment): Response
    {
        $this->assertCourseReadable($request, $course);

        $this->assertEnrollmentVisible($enrollment);

        if ((string) $enrollment->course_id !== (string) $course->id) {
            abort(404);
        }

        $enrollment->loadMissing([
            'user.company',
            'course:id,title,slug,action_code',
        ]);

        /** @var User|null $user */
        $user = $enrollment->user;

        $company = $user?->company;

        $sessions = DB::table('watch_time_sessions')
            ->leftJoin('lessons', 'lessons.id', '=', 'watch_time_sessions.lesson_id')
            ->where('watch_time_sessions.enrollment_id', $enrollment->id)
            ->orderByDesc('watch_time_sessions.started_at')
            ->get([
                'watch_time_sessions.*',
                DB::raw('lessons.title as lesson_title'),
            ]);

        $sessionSummary = [
            'count' => $sessions->count(),
            'video_seconds' => (int) $sessions->sum(fn ($r) => (int) ($r->video_seconds ?? 0)),
            'scorm_seconds' => (int) $sessions->sum(fn ($r) => (int) ($r->scorm_seconds ?? 0)),
            'document_seconds' => (int) $sessions->sum(fn ($r) => (int) ($r->document_seconds ?? 0)),
            'total_seconds' => (int) $sessions->sum(fn ($r) => (int) ($r->total_seconds ?? 0)),
        ];

        $platformSessions = $this->platformSessionsFor($user, $enrollment);

        $platformSummary = [
            'count' => $platformSessions->count(),
            'total_seconds' => (int) $platformSessions->sum(fn ($r) => (int) ($r->total_seconds ?? 0)),
            'avg_seconds' => $platformSessions->count() > 0
                ? (int) round(((int) $platformSessions->sum(fn ($r) => (int) ($r->total_seconds ?? 0))) / max(1, $platformSessions->count()))
                : 0,
        ];

        /** @var Tenant|null $tenant */
        $tenant = tenant();
        $pdfData = (is_array($tenant?->pdf_course_report) ? $tenant->pdf_course_report : []);

        $accent = (string) ($pdfData['accent'] ?? '');
        $accent = preg_match('/^#[0-9a-f]{6}$/i', $accent) ? $accent : '#f59e0b';
        $header = (string) ($pdfData['header'] ?? '');
        $footer = (string) ($pdfData['footer'] ?? '');

        $logoDataUri = TenantPdfLogo::dataUri();

        $pdf = Pdf::loadView('tenant.admin.courses.learner-report-pdf', [
            'course' => $course,
            'enrollment' => $enrollment,
            'user' => $user,
            'company' => $company,
            'sessions' => $sessions,
            'sessionSummary' => $sessionSummary,
            'platformSessions' => $platformSessions,
            'platformSummary' => $platformSummary,
            'tenantName' => (string) ($tenant?->organization_name ?? $tenant?->id ?? ''),
            'accent' => $accent,
            'headerText' => $header,
            'footerText' => $footer,
            'logoDataUri' => $logoDataUri,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string) ($user?->email ?? $user?->name ?? 'corsista')) ?: 'corsista';
        $filename = 'report-corsista-'.$course->slug.'-'.$safeName.'.pdf';

        return $pdf->download($filename);
    }

    public function learnerTime(Request $request, Course $course, Enrollment $enrollment)
    {
        $this->assertCourseReadable($request, $course);

        $this->assertEnrollmentVisible($enrollment);

        if ((string) $enrollment->course_id !== (string) $course->id) {
            abort(404);
        }

        $enrollment->loadMissing(['user:id,name,email,company_id']);

        $sessions = DB::table('watch_time_sessions')
            ->leftJoin('lessons', 'lessons.id', '=', 'watch_time_sessions.lesson_id')
            ->where('watch_time_sessions.enrollment_id', $enrollment->id)
            ->orderByDesc('watch_time_sessions.started_at')
            ->get([
                'watch_time_sessions.*',
                DB::raw('lessons.title as lesson_title'),
            ]);

        $sessionSummary = [
            'count' => $sessions->count(),
            'video_seconds' => (int) $sessions->sum(fn ($r) => (int) ($r->video_seconds ?? 0)),
            'scorm_seconds' => (int) $sessions->sum(fn ($r) => (int) ($r->scorm_seconds ?? 0)),
            'document_seconds' => (int) $sessions->sum(fn ($r) => (int) ($r->document_seconds ?? 0)),
            'total_seconds' => (int) $sessions->sum(fn ($r) => (int) ($r->total_seconds ?? 0)),
        ];

        return view('tenant.admin.courses.learner-time', [
            'course' => $course,
            'enrollment' => $enrollment,
            'sessions' => $sessions,
            'sessionSummary' => $sessionSummary,
            'platformSessions' => $this->platformSessionsFor($enrollment->user, $enrollment),
            'lessons' => $this->lessonsForCourse($course),
        ]);
    }

    public function storeLearnerTimeSession(Request $request, Course $course, Enrollment $enrollment, WatchTimeSessionService $sessions, SessionLessonProgressService $lessonProgress)
    {
        $this->abortUnlessEnrollmentOnCourse($course, $enrollment);

        $data = $this->validateSessionTimes($request, $course);
        $sessions->createManual(
            (string) $enrollment->id,
            (string) $enrollment->user_id,
            (string) $course->id,
            $data['lesson_id'] ?? null,
            $this->sessionInstant($data['started_at']),
            $this->sessionInstant($data['ended_at']),
            max(0, (int) $data['video_minutes']) * 60,
            max(0, (int) $data['scorm_minutes']) * 60,
            $request->user()?->id,
        );

        return $this->redirectAfterSessionChange($enrollment, 'Sessione aggiunta.', [$data['lesson_id'] ?? null], $lessonProgress);
    }

    public function updateLearnerTimeSession(Request $request, Course $course, Enrollment $enrollment, string $session, WatchTimeSessionService $sessions, SessionLessonProgressService $lessonProgress)
    {
        $this->abortUnlessEnrollmentOnCourse($course, $enrollment);

        $previousLessonId = DB::table('watch_time_sessions')
            ->where('id', $session)
            ->where('enrollment_id', $enrollment->id)
            ->where('course_id', $course->id)
            ->value('lesson_id');

        $data = $this->validateSessionTimes($request, $course);
        $updated = $sessions->updateManual(
            $session,
            (string) $enrollment->id,
            (string) $course->id,
            $data['lesson_id'] ?? null,
            $this->sessionInstant($data['started_at']),
            $this->sessionInstant($data['ended_at']),
            max(0, (int) $data['video_minutes']) * 60,
            max(0, (int) $data['scorm_minutes']) * 60,
            $request->user()?->id,
        );

        if (! $updated) {
            abort(404);
        }

        return $this->redirectAfterSessionChange(
            $enrollment,
            'Sessione aggiornata.',
            [$previousLessonId, $data['lesson_id'] ?? null],
            $lessonProgress,
        );
    }

    public function destroyLearnerTimeSession(Request $request, Course $course, Enrollment $enrollment, string $session, WatchTimeSessionService $sessions, SessionLessonProgressService $lessonProgress)
    {
        $this->abortUnlessEnrollmentOnCourse($course, $enrollment);

        $lessonId = DB::table('watch_time_sessions')
            ->where('id', $session)
            ->where('enrollment_id', $enrollment->id)
            ->where('course_id', $course->id)
            ->value('lesson_id');

        $deleted = $sessions->deleteManual($session, (string) $enrollment->id, (string) $course->id);
        if (! $deleted) {
            abort(404);
        }

        return $this->redirectAfterSessionChange($enrollment, 'Sessione eliminata.', [$lessonId], $lessonProgress);
    }

    public function importLearnerTimeSessions(Request $request, Course $course, Enrollment $enrollment, WatchTimeSessionImportService $import, WatchTimeSessionService $sessions, SessionLessonProgressService $lessonProgress)
    {
        $this->abortUnlessEnrollmentOnCourse($course, $enrollment);

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [
            'csv_file.required' => 'Seleziona un file CSV.',
            'csv_file.mimes' => 'Il file deve essere un CSV.',
            'csv_file.max' => 'Il file supera i 2 MB.',
        ]);

        $lessonIds = [];
        foreach ($this->lessonsForCourse($course) as $lesson) {
            $key = mb_strtolower(trim((string) $lesson->title));
            if ($key !== '' && ! isset($lessonIds[$key])) {
                $lessonIds[$key] = (string) $lesson->id;
            }
        }

        $parsed = $import->parse($request->file('csv_file')->getRealPath(), $lessonIds);
        $actorId = $request->user()?->id;

        DB::transaction(function () use ($parsed, $sessions, $enrollment, $course, $actorId) {
            foreach ($parsed['rows'] as $row) {
                $sessions->createManual(
                    (string) $enrollment->id,
                    (string) $enrollment->user_id,
                    (string) $course->id,
                    $row['lesson_id'],
                    $row['started_at'],
                    $row['ended_at'],
                    $row['video_seconds'],
                    $row['scorm_seconds'],
                    $actorId,
                );
            }
        });

        $created = count($parsed['rows']);
        $toast = match (true) {
            $created === 0 => 'Nessuna sessione importata.',
            $created === 1 => 'Importata 1 sessione.',
            default => "Importate {$created} sessioni.",
        };
        if ($parsed['errors'] !== [] && $created > 0) {
            $toast .= ' Alcune righe non sono state importate.';
        }

        $touchedLessons = [];
        foreach ($parsed['rows'] as $row) {
            if (is_string($row['lesson_id'] ?? null) && $row['lesson_id'] !== '') {
                $touchedLessons[] = $row['lesson_id'];
            }
        }

        if ($created > 0 && $touchedLessons !== []) {
            $before = (float) $enrollment->progress_pct;
            $notes = $lessonProgress->sync($enrollment, $touchedLessons);
            $enrollment->refresh();
            $after = (float) $enrollment->progress_pct;
            if (round($after, 2) !== round($before, 2)) {
                $toast .= ' Completamento corso: '.number_format($after, 0).'%.';
            } elseif ($notes !== []) {
                $toast .= ' '.$notes[0];
            }
        }

        return back()
            ->with('toast', $toast)
            ->with('import_errors', $parsed['errors']);
    }

    /**
     * @param  list<string|null>  $lessonIds
     */
    private function redirectAfterSessionChange(Enrollment $enrollment, string $message, array $lessonIds, SessionLessonProgressService $lessonProgress)
    {
        $before = (float) $enrollment->progress_pct;
        $notes = $lessonProgress->sync($enrollment, $lessonIds);
        $enrollment->refresh();
        $after = (float) $enrollment->progress_pct;

        if (round($after, 2) !== round($before, 2)) {
            $message .= ' Completamento corso: '.number_format($after, 0).'%.';
        } elseif ($notes !== []) {
            $message .= ' '.$notes[0];
        }

        return back()->with('toast', $message);
    }

    public function learnerTimeSessionsTemplate(Course $course, Enrollment $enrollment): StreamedResponse
    {
        $this->abortUnlessEnrollmentOnCourse($course, $enrollment);

        $filename = 'modello-sessioni.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'wb');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['inizio', 'fine', 'minuti_video', 'minuti_scorm', 'lezione'], ';');
            fputcsv($out, ['28/03/2026 09:00', '28/03/2026 10:30', '90', '0', ''], ';');
            fputcsv($out, ['28/03/2026 14:00', '28/03/2026 15:00', '0', '60', 'Introduzione'], ';');
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return Collection<int, object>
     */
    private function platformSessionsFor(?User $user, Enrollment $enrollment)
    {
        if ($user === null) {
            return collect();
        }

        return DB::table('platform_sessions')
            ->where('user_id', $user->id)
            ->when($enrollment->enrolled_at, fn ($query) => $query->where('started_at', '>=', $enrollment->enrolled_at))
            ->orderByDesc('started_at')
            ->get();
    }

    private function abortUnlessEnrollmentOnCourse(Course $course, Enrollment $enrollment): void
    {
        if ((string) $enrollment->course_id !== (string) $course->id) {
            abort(404);
        }

        $this->assertEnrollmentVisible($enrollment);
    }

    private function assertEnrollmentVisible(Enrollment $enrollment): void
    {
        $viewer = auth()->user();
        if (! $viewer instanceof User || ! $viewer->isCompanyManager()) {
            return;
        }

        $enrollment->loadMissing('user:id,company_id');

        abort_unless(
            $viewer->company_id !== null
            && (string) $enrollment->user?->company_id === (string) $viewer->company_id,
            404,
        );
    }

    /**
     * L'admin scrive l'orario italiano; in database resta UTC.
     */
    private function sessionInstant(string $value): Carbon
    {
        return Date::parse($value, 'Europe/Rome');
    }

    /**
     * @return array{started_at: string, ended_at: string, video_minutes: int, scorm_minutes: int, lesson_id: ?string}
     */
    private function validateSessionTimes(Request $request, Course $course): array
    {
        return $request->validate([
            'started_at' => ['required', 'date'],
            'ended_at' => ['required', 'date', 'after_or_equal:started_at'],
            'video_minutes' => ['required', 'integer', 'min:0', 'max:1000000'],
            'scorm_minutes' => ['required', 'integer', 'min:0', 'max:1000000'],
            'lesson_id' => ['nullable', 'uuid', Rule::exists('lessons', 'id')->where(function ($query) use ($course) {
                $query->whereIn('module_id', function ($sub) use ($course) {
                    $sub->select('module_id')->from('course_module')->where('course_id', $course->id);
                });
            })],
        ], [
            'started_at.required' => 'Indica l\'inizio della sessione.',
            'started_at.date' => 'L\'inizio non è una data valida.',
            'ended_at.required' => 'Indica la fine della sessione.',
            'ended_at.date' => 'La fine non è una data valida.',
            'ended_at.after_or_equal' => 'La fine deve essere uguale o successiva all\'inizio.',
            'video_minutes.required' => 'Indica i minuti video.',
            'video_minutes.integer' => 'I minuti video devono essere un numero intero.',
            'video_minutes.min' => 'I minuti video non possono essere negativi.',
            'scorm_minutes.required' => 'Indica i minuti SCORM.',
            'scorm_minutes.integer' => 'I minuti SCORM devono essere un numero intero.',
            'scorm_minutes.min' => 'I minuti SCORM non possono essere negativi.',
            'lesson_id.exists' => 'La lezione non appartiene a questo corso.',
            'lesson_id.uuid' => 'La lezione non è valida.',
        ]);
    }

    private function lessonsForCourse(Course $course)
    {
        return Lesson::query()
            ->select('lessons.*')
            ->join('course_module', 'course_module.module_id', '=', 'lessons.module_id')
            ->where('course_module.course_id', $course->id)
            ->orderBy('course_module.position')
            ->orderBy('lessons.position')
            ->orderBy('lessons.title')
            ->get();
    }

    /**
     * Query iscrizioni con secondi visti da sessioni; opzionalmente join su ultimo sync video/SCORM (lista corsisti).
     */
    private function assertCourseReadable(Request $request, Course $course): void
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $user->canReadCourseReports($course)) {
            abort(403, 'Non sei autorizzato a visualizzare i report di questo corso.');
        }
    }

    private function enrollmentReportBaseQuery(Course $course, bool $includeLastActivity): Builder
    {
        $sessionAgg = $this->courseSessionTotalsAggregate($course);

        $q = Enrollment::query()
            ->where('course_id', $course->id)
            ->with(['user:id,name,email'])
            ->leftJoinSub($sessionAgg, 'sess', function ($join) {
                $join->on('sess.enrollment_id', '=', 'enrollments.id');
            })
            ->select([
                'enrollments.*',
                DB::raw('COALESCE(sess.session_total_seconds, 0) as watched_seconds_total'),
                DB::raw('COALESCE(sess.session_video_seconds, 0) as video_seconds_total'),
            ]);

        if ($includeLastActivity) {
            [$videoAgg, $scormAgg] = $this->courseLastActivityAggregates($course);
            $q->leftJoinSub($videoAgg, 'vagg', function ($join) {
                $join->on('vagg.enrollment_id', '=', 'enrollments.id');
            })
                ->leftJoinSub($scormAgg, 'sagg', function ($join) {
                    $join->on('sagg.enrollment_id', '=', 'enrollments.id');
                })
                ->addSelect([
                    DB::raw('vagg.last_video_sync_at as last_video_sync_at'),
                    DB::raw('sagg.last_scorm_sync_at as last_scorm_sync_at'),
                ]);
        }

        return $this->restrictReportToViewerCompany($q);
    }

    private function restrictReportToViewerCompany(Builder $query): Builder
    {
        $viewer = auth()->user();
        if (! $viewer instanceof User || ! $viewer->isCompanyManager()) {
            return $query;
        }

        if ($viewer->company_id === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereHas('user', fn (Builder $user) => $user->where('company_id', $viewer->company_id));
    }

    private function courseSessionTotalsAggregate(Course $course)
    {
        return DB::table('watch_time_sessions')
            ->where('course_id', $course->id)
            ->selectRaw('enrollment_id')
            ->selectRaw('SUM(total_seconds) as session_total_seconds')
            ->selectRaw('SUM(video_seconds) as session_video_seconds')
            ->groupBy('enrollment_id');
    }

    private function companyAggregatesForCourse(Course $course)
    {
        $sessionAgg = $this->courseSessionTotalsAggregate($course);

        $query = Enrollment::query()
            ->where('enrollments.course_id', $course->id)
            ->join('users', 'users.id', '=', 'enrollments.user_id');

        $viewer = auth()->user();
        if ($viewer instanceof User && $viewer->isCompanyManager()) {
            if ($viewer->company_id === null) {
                $query->whereRaw('0 = 1');
            } else {
                $query->where('users.company_id', $viewer->company_id);
            }
        }

        return $query
            ->leftJoin('companies', 'companies.id', '=', 'users.company_id')
            ->leftJoinSub($sessionAgg, 'sess', function ($join) {
                $join->on('sess.enrollment_id', '=', 'enrollments.id');
            })
            ->select([
                DB::raw('companies.id as company_id'),
                DB::raw('companies.name as company_name'),
                DB::raw('COUNT(DISTINCT enrollments.user_id) as learners_count'),
                DB::raw('SUM(COALESCE(sess.session_total_seconds, 0)) as total_seconds'),
                DB::raw("SUM(CASE WHEN enrollments.status = 'completed' THEN 1 ELSE 0 END) as completed_enrollments"),
            ])
            ->groupBy('companies.id', 'companies.name')
            ->orderByRaw('companies.name is null, companies.name asc');
    }

    /**
     * @return array{0:\Illuminate\Database\Query\Builder,1:\Illuminate\Database\Query\Builder}
     */
    private function courseLastActivityAggregates(Course $course): array
    {
        $videoAgg = DB::table('video_progress')
            ->join('video_lessons', 'video_lessons.id', '=', 'video_progress.video_lesson_id')
            ->join('lessons', 'lessons.id', '=', 'video_lessons.lesson_id')
            ->join('course_module', 'course_module.module_id', '=', 'lessons.module_id')
            ->where('course_module.course_id', $course->id)
            ->selectRaw('video_progress.enrollment_id as enrollment_id')
            ->selectRaw('MAX(video_progress.last_sync_at) as last_video_sync_at')
            ->groupBy('video_progress.enrollment_id');

        $scormAgg = DB::table('scorm_trackings')
            ->join('scorm_packages', 'scorm_packages.id', '=', 'scorm_trackings.scorm_package_id')
            ->join('lessons', 'lessons.id', '=', 'scorm_packages.lesson_id')
            ->join('course_module', 'course_module.module_id', '=', 'lessons.module_id')
            ->where('course_module.course_id', $course->id)
            ->selectRaw('scorm_trackings.enrollment_id as enrollment_id')
            ->selectRaw('MAX(scorm_trackings.last_sync_at) as last_scorm_sync_at')
            ->groupBy('scorm_trackings.enrollment_id');

        return [$videoAgg, $scormAgg];
    }

    private function decorateEnrollmentsForReport(iterable $items, Carbon $activeCutoff): void
    {
        foreach ($items as $e) {
            $lastVideo = $e->last_video_sync_at ? Carbon::parse($e->last_video_sync_at) : null;
            $lastScorm = $e->last_scorm_sync_at ? Carbon::parse($e->last_scorm_sync_at) : null;
            $lastActivity = $lastVideo;
            if ($lastScorm && (! $lastActivity || $lastScorm->greaterThan($lastActivity))) {
                $lastActivity = $lastScorm;
            }

            $videoSeconds = (int) ($e->video_seconds_total ?? 0);
            $e->minutes_watched = (int) floor($videoSeconds / 60);
            $e->last_activity_at = $lastActivity;
            $activeVideo = $lastVideo ? $lastVideo->greaterThanOrEqualTo($activeCutoff) : false;
            $activeScorm = $lastScorm ? $lastScorm->greaterThanOrEqualTo($activeCutoff) : false;
            $e->is_watching_now = $activeVideo || $activeScorm;
        }
    }

    public function create()
    {
        return redirect()->route('tenant.admin.courses.index', ['create' => 1]);
    }

    public function store(Request $request, TenantQuotaService $quota)
    {
        $data = $this->validatedCourseFields($request);

        $slug = $this->uniqueSlug($data['slug'] ?: $data['title']);

        $course = Course::create([
            'title' => $data['title'],
            'action_code' => $data['action_code'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'thumbnail' => null,
            'status' => $data['status'],
            'settings' => null,
            'starts_at' => $data['starts_at'] ?? null,
            'total_hours' => $data['total_hours'] ?? null,
            'auto_enroll' => $data['auto_enroll'],
            'schedule_enabled' => $data['schedule_enabled'],
            'schedule_weekdays' => $data['schedule_weekdays'],
            'schedule_opens_at' => $data['schedule_opens_at'],
            'schedule_closes_at' => $data['schedule_closes_at'],
            'night_schedule_enabled' => $data['night_schedule_enabled'],
            'night_opens_at' => $data['night_opens_at'],
            'night_closes_at' => $data['night_closes_at'],
        ]);

        $this->syncCourseThumbnail($request, $course);
        $course->assignedCompanies()->sync($data['assigned_company_ids'] ?? []);
        $course->assignedUsers()->sync($this->normalizeAssignedLearnerIds($data['assigned_user_ids'] ?? []));

        $enrolled = app(CourseAutoEnrollmentService::class)->syncForCourse($course->fresh());

        $toast = 'Corso creato.';
        if ($enrolled > 0) {
            $toast .= " {$enrolled} iscrizioni automatiche create.";
        }

        return redirect()
            ->route('tenant.admin.courses.edit', $course)
            ->with('toast', $toast);
    }

    public function edit(Course $course)
    {
        $course->loadMissing(['assignedCompanies', 'assignedUsers']);

        return view('tenant.admin.courses.form', [
            'course' => $course,
            'statuses' => CourseStatus::cases(),
            'companies' => Company::query()->orderBy('name')->get(),
            'learners' => User::query()->where('role', UserRole::Learner)->orderBy('name')->get(),
            'assignedCompanyIds' => $course->assignedCompanies->pluck('id')->all(),
            'assignedLearnerIds' => $course->assignedUsers->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Course $course, TenantQuotaService $quota)
    {
        $data = $this->validatedCourseFields($request);

        $slug = $data['slug'] !== ''
            ? $this->uniqueSlug($data['slug'], ignoreId: $course->id)
            : $this->uniqueSlug($data['title'], ignoreId: $course->id);

        $course->update([
            'title' => $data['title'],
            'action_code' => $data['action_code'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'starts_at' => $data['starts_at'] ?? null,
            'total_hours' => $data['total_hours'] ?? null,
            'auto_enroll' => $data['auto_enroll'],
            'schedule_enabled' => $data['schedule_enabled'],
            'schedule_weekdays' => $data['schedule_weekdays'],
            'schedule_opens_at' => $data['schedule_opens_at'],
            'schedule_closes_at' => $data['schedule_closes_at'],
            'night_schedule_enabled' => $data['night_schedule_enabled'],
            'night_opens_at' => $data['night_opens_at'],
            'night_closes_at' => $data['night_closes_at'],
        ]);

        $this->syncCourseThumbnail($request, $course);
        $course->assignedCompanies()->sync($data['assigned_company_ids'] ?? []);
        $course->assignedUsers()->sync($this->normalizeAssignedLearnerIds($data['assigned_user_ids'] ?? []));

        $enrolled = app(CourseAutoEnrollmentService::class)->syncForCourse($course->fresh());

        $toast = 'Corso aggiornato.';
        if ($enrolled > 0) {
            $toast .= " {$enrolled} iscrizioni automatiche create.";
        }

        return back()->with('toast', $toast);
    }

    public function destroy(Course $course)
    {
        if ($course->thumbnail) {
            Storage::disk(MediaStorage::disk())->delete($course->thumbnail);
        }

        $course->delete();

        return redirect()
            ->route('tenant.admin.courses.index')
            ->with('toast', 'Corso eliminato.');
    }

    private function validateCourse(Request $request): array
    {
        $statusValues = array_map(fn (CourseStatus $s) => $s->value, CourseStatus::cases());

        return $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:200'],
            'action_code' => ['nullable', 'string', 'max:64'],
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::in($statusValues)],
            'starts_at' => ['nullable', 'date'],
            'duration_hours' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:59'],
            'thumbnail' => ['nullable', 'file', 'image', 'max:5120'],
            'remove_thumbnail' => ['sometimes', 'boolean'],
            'assigned_company_ids' => ['nullable', 'array', 'max:500'],
            'assigned_company_ids.*' => ['uuid', 'exists:companies,id'],
            'assigned_user_ids' => ['nullable', 'array', 'max:500'],
            'assigned_user_ids.*' => ['uuid', 'exists:users,id'],
            'auto_enroll' => ['sometimes', 'boolean'],
            'schedule_enabled' => ['sometimes', 'boolean'],
            'schedule_weekdays' => ['nullable', 'array', 'max:7'],
            'schedule_weekdays.*' => ['integer', 'min:1', 'max:7'],
            'schedule_opens_at' => ['nullable', 'date_format:H:i'],
            'schedule_closes_at' => ['nullable', 'date_format:H:i'],
            'night_schedule_enabled' => ['sometimes', 'boolean'],
            'night_opens_at' => ['nullable', 'date_format:H:i'],
            'night_closes_at' => ['nullable', 'date_format:H:i'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedCourseFields(Request $request): array
    {
        $data = $this->validateCourse($request);
        $data['action_code'] = trim((string) ($data['action_code'] ?? '')) ?: null;
        $data['auto_enroll'] = $request->boolean('auto_enroll');
        $data['schedule_enabled'] = $request->boolean('schedule_enabled');
        $data['night_schedule_enabled'] = $request->boolean('night_schedule_enabled');

        $hoursFilled = $request->filled('duration_hours');
        $minutesFilled = $request->filled('duration_minutes');

        if (! $hoursFilled && ! $minutesFilled) {
            $data['total_hours'] = null;
        } else {
            $h = (int) ($data['duration_hours'] ?? 0);
            $m = (int) ($data['duration_minutes'] ?? 0);
            $data['total_hours'] = round(($h * 60 + $m) / 60, 2);
        }

        unset($data['duration_hours'], $data['duration_minutes']);

        if ($data['schedule_enabled']) {
            $weekdays = collect($data['schedule_weekdays'] ?? [])
                ->map(fn ($d) => (int) $d)
                ->filter(fn (int $d) => $d >= 1 && $d <= 7)
                ->unique()
                ->sort()
                ->values()
                ->all();

            if ($weekdays === []) {
                throw ValidationException::withMessages([
                    'schedule_weekdays' => 'Seleziona almeno un giorno della settimana.',
                ]);
            }

            if (empty($data['schedule_opens_at']) || empty($data['schedule_closes_at'])) {
                throw ValidationException::withMessages([
                    'schedule_opens_at' => 'Imposta orario di apertura e chiusura.',
                ]);
            }

            $data['schedule_weekdays'] = $weekdays;
            $data['schedule_opens_at'] = $data['schedule_opens_at'];
            $data['schedule_closes_at'] = $data['schedule_closes_at'];
        } else {
            $data['schedule_weekdays'] = null;
            $data['schedule_opens_at'] = null;
            $data['schedule_closes_at'] = null;
            $data['night_schedule_enabled'] = false;
            $data['night_opens_at'] = null;
            $data['night_closes_at'] = null;
        }

        if ($data['schedule_enabled'] && $data['night_schedule_enabled']) {
            if (empty($data['night_opens_at']) || empty($data['night_closes_at'])) {
                throw ValidationException::withMessages([
                    'night_opens_at' => 'Imposta orario di apertura e chiusura della fascia notturna.',
                ]);
            }
        } else {
            $data['night_schedule_enabled'] = false;
            $data['night_opens_at'] = null;
            $data['night_closes_at'] = null;
        }

        return $data;
    }

    /**
     * @param  array<int, string>  $userIds
     * @return array<int, string>
     */
    private function normalizeAssignedLearnerIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->where('role', UserRole::Learner)
            ->pluck('id')
            ->all();
    }

    private function syncCourseThumbnail(Request $request, Course $course): void
    {
        $disk = MediaStorage::disk();

        if ($request->hasFile('thumbnail')) {
            /** @var UploadedFile $file */
            $file = $request->file('thumbnail');
            if (! $file->isValid()) {
                return;
            }

            $tenantId = (string) (tenant('id') ?? 'central');
            $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg'));
            if ($ext === '') {
                $ext = 'jpg';
            }

            $relativePath = 'tenants/'.$tenantId.'/courses/'.$course->id.'/cover.'.$ext;

            if ($course->thumbnail) {
                Storage::disk($disk)->delete($course->thumbnail);
            }

            $storedPath = UploadedFileStorage::put($file, $disk, $relativePath);
            if ($storedPath !== false) {
                $course->forceFill(['thumbnail' => $storedPath])->save();
            }

            return;
        }

        if ($request->boolean('remove_thumbnail')) {
            if ($course->thumbnail) {
                Storage::disk($disk)->delete($course->thumbnail);
            }
            $course->forceFill(['thumbnail' => null])->save();
        }
    }

    private function uniqueSlug(string $input, ?string $ignoreId = null): string
    {
        $base = Str::slug($input);
        $slug = $base;
        $i = 2;

        while (Course::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
