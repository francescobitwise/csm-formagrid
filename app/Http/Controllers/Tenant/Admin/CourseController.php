<?php

namespace App\Http\Controllers\Tenant\Admin;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Course;
use App\Models\Tenant\Company;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\User;
use App\Services\TenantQuotaService;
use App\Support\MediaStorage;
use App\Support\TenantPdfLogo;
use App\Support\UploadedFileStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $courses = Course::query()
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
        ]);
    }

    public function learners(Request $request, Course $course)
    {
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

    public function companiesReport(Request $request, Course $course): \Illuminate\View\View
    {
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

        $pdf = Pdf::loadView('tenant.admin.courses.learners-pdf', [
            'course' => $course,
            'rows' => $rows,
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

    public function learnerTime(Request $request, Course $course, Enrollment $enrollment)
    {
        if ((string) $enrollment->course_id !== (string) $course->id) {
            abort(404);
        }

        $enrollment->loadMissing(['user:id,name,email']);

        $sessions = DB::table('watch_time_sessions')
            ->where('enrollment_id', $enrollment->id)
            ->orderByDesc('started_at')
            ->get();

        $sessionSummary = [
            'count' => $sessions->count(),
            'video_seconds' => (int) $sessions->sum(fn ($r) => (int) ($r->video_seconds ?? 0)),
            'scorm_seconds' => (int) $sessions->sum(fn ($r) => (int) ($r->scorm_seconds ?? 0)),
            'total_seconds' => (int) $sessions->sum(fn ($r) => (int) ($r->total_seconds ?? 0)),
        ];

        return view('tenant.admin.courses.learner-time', [
            'course' => $course,
            'enrollment' => $enrollment,
            'sessions' => $sessions,
            'sessionSummary' => $sessionSummary,
            'sessionGapSeconds' => max(60, (int) config('analytics.watch_time_session_gap_seconds', 1800)),
        ]);
    }

    public function updateLearnerTimeSession(Request $request, Course $course, Enrollment $enrollment, string $session)
    {
        if ((string) $enrollment->course_id !== (string) $course->id) {
            abort(404);
        }

        $data = $request->validate([
            'started_at' => ['required', 'date'],
            'ended_at' => ['required', 'date', 'after_or_equal:started_at'],
            'video_minutes' => ['required', 'integer', 'min:0', 'max:1000000'],
            'scorm_minutes' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);

        $actor = $request->user();
        $videoSeconds = max(0, (int) $data['video_minutes']) * 60;
        $scormSeconds = max(0, (int) $data['scorm_minutes']) * 60;
        $totalSeconds = $videoSeconds + $scormSeconds;

        DB::table('watch_time_sessions')
            ->where('id', $session)
            ->where('enrollment_id', $enrollment->id)
            ->where('course_id', $course->id)
            ->update([
                'started_at' => Date::parse($data['started_at']),
                'ended_at' => Date::parse($data['ended_at']),
                'video_seconds' => $videoSeconds,
                'scorm_seconds' => $scormSeconds,
                'total_seconds' => $totalSeconds,
                'updated_by_user_id' => $actor?->id,
                'updated_at' => now(),
            ]);

        return back()->with('toast', 'Sessione aggiornata.');
    }

    /**
     * Query iscrizioni con secondi visti da sessioni; opzionalmente join su ultimo sync video/SCORM (lista corsisti).
     */
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

        return $q;
    }

    private function courseSessionTotalsAggregate(Course $course)
    {
        return DB::table('watch_time_sessions')
            ->where('course_id', $course->id)
            ->selectRaw('enrollment_id')
            ->selectRaw('SUM(total_seconds) as session_total_seconds')
            ->groupBy('enrollment_id');
    }

    private function companyAggregatesForCourse(Course $course)
    {
        $sessionAgg = $this->courseSessionTotalsAggregate($course);

        return Enrollment::query()
            ->where('enrollments.course_id', $course->id)
            ->join('users', 'users.id', '=', 'enrollments.user_id')
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

            $totalSeconds = (int) ($e->watched_seconds_total ?? 0);
            $e->minutes_watched = (int) floor($totalSeconds / 60);
            $e->last_activity_at = $lastActivity;
            $activeVideo = $lastVideo ? $lastVideo->greaterThanOrEqualTo($activeCutoff) : false;
            $activeScorm = $lastScorm ? $lastScorm->greaterThanOrEqualTo($activeCutoff) : false;
            $e->is_watching_now = $activeVideo || $activeScorm;
        }
    }

    public function create()
    {
        return view('tenant.admin.courses.form', [
            'course' => new Course,
            'statuses' => CourseStatus::cases(),
            'companies' => Company::query()->orderBy('name')->get(),
            'learners' => User::query()->where('role', \App\Enums\UserRole::Learner)->orderBy('name')->get(),
            'assignedCompanyIds' => [],
            'assignedLearnerIds' => [],
        ]);
    }

    public function store(Request $request, TenantQuotaService $quota)
    {
        $data = $this->validateCourse($request);

        $slug = $this->uniqueSlug($data['slug'] ?: $data['title']);

        $course = Course::create([
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'thumbnail' => null,
            'status' => $data['status'],
            'settings' => null,
            'starts_at' => $data['starts_at'] ?? null,
            'total_hours' => $data['total_hours'] ?? null,
        ]);

        $this->syncCourseThumbnail($request, $course);
        $course->assignedCompanies()->sync($data['assigned_company_ids'] ?? []);
        $course->assignedUsers()->sync($this->normalizeAssignedLearnerIds($data['assigned_user_ids'] ?? []));

        return redirect()
            ->route('tenant.admin.courses.edit', $course)
            ->with('toast', 'Corso creato.');
    }

    public function edit(Course $course)
    {
        $course->loadMissing(['assignedCompanies', 'assignedUsers']);

        return view('tenant.admin.courses.form', [
            'course' => $course,
            'statuses' => CourseStatus::cases(),
            'companies' => Company::query()->orderBy('name')->get(),
            'learners' => User::query()->where('role', \App\Enums\UserRole::Learner)->orderBy('name')->get(),
            'assignedCompanyIds' => $course->assignedCompanies->pluck('id')->all(),
            'assignedLearnerIds' => $course->assignedUsers->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Course $course, TenantQuotaService $quota)
    {
        $data = $this->validateCourse($request);

        $slug = $data['slug'] !== ''
            ? $this->uniqueSlug($data['slug'], ignoreId: $course->id)
            : $this->uniqueSlug($data['title'], ignoreId: $course->id);

        $course->update([
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'starts_at' => $data['starts_at'] ?? null,
            'total_hours' => $data['total_hours'] ?? null,
        ]);

        $this->syncCourseThumbnail($request, $course);
        $course->assignedCompanies()->sync($data['assigned_company_ids'] ?? []);
        $course->assignedUsers()->sync($this->normalizeAssignedLearnerIds($data['assigned_user_ids'] ?? []));

        return back()->with('toast', 'Corso aggiornato.');
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
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::in($statusValues)],
            'starts_at' => ['nullable', 'date'],
            'total_hours' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'thumbnail' => ['nullable', 'file', 'image', 'max:5120'],
            'remove_thumbnail' => ['sometimes', 'boolean'],
            'assigned_company_ids' => ['nullable', 'array', 'max:500'],
            'assigned_company_ids.*' => ['uuid', 'exists:companies,id'],
            'assigned_user_ids' => ['nullable', 'array', 'max:500'],
            'assigned_user_ids.*' => ['uuid', 'exists:users,id'],
        ]);
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
            ->where('role', \App\Enums\UserRole::Learner)
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
