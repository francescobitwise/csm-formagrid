<?php

namespace App\Http\Controllers\Tenant\Admin;

use App\Enums\LessonType;
use App\Enums\ProcessingStatus;
use App\Enums\ScormVersion;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessScormPackageUploadJob;
use App\Jobs\ProcessVideoLessonUploadJob;
use App\Models\Tenant\DocumentLesson;
use App\Models\Tenant\Lesson;
use App\Models\Tenant\Module;
use App\Models\Tenant\ScormPackage;
use App\Models\Tenant\VideoLesson;
use App\Services\TenantQuotaService;
use App\Support\DurationFormat;
use App\Support\LessonDuration;
use App\Support\MediaStorage;
use App\Support\UploadedFileStorage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ModuleLessonController extends Controller
{
    private function durationSecondsFromRequest(Request $request, array $data): ?int
    {
        $minRaw = $request->input('duration_minutes');
        $secRaw = $request->input('duration_seconds');

        $hasParts = ($minRaw !== null && trim((string) $minRaw) !== '') || ($secRaw !== null && trim((string) $secRaw) !== '');
        if ($hasParts) {
            $minutes = (int) ($minRaw ?? 0);
            $seconds = (int) ($secRaw ?? 0);

            if ($minutes < 0 || $seconds < 0 || $seconds > 59) {
                return -1;
            }

            return $minutes * 60 + $seconds;
        }

        return DurationFormat::mmssToSeconds($data['duration_mmss'] ?? null);
    }

    public function show(Module $module)
    {
        $module->load([
            'lessons' => fn ($q) => $q->orderBy('position'),
            'lessons.videoLesson',
            'lessons.scormPackage',
            'lessons.documentLesson',
            'courses:id,title,slug',
        ]);

        $totals = LessonDuration::sumForLessons($module->lessons);

        return view('tenant.admin.modules.lessons', [
            'module' => $module,
            'lessonTypes' => LessonType::cases(),
            'moduleTotalDurationSeconds' => $totals['total_seconds'],
            'moduleLessonDurationCount' => $totals['lesson_count_with_duration'],
        ]);
    }

    public function contentStatus(Module $module)
    {
        $module->load([
            'lessons.videoLesson',
            'lessons.scormPackage',
        ]);

        $items = [];
        foreach ($module->lessons as $lesson) {
            $type = (string) ($lesson->type?->value ?? $lesson->type);
            if ($type === LessonType::Video->value) {
                $status = (string) ($lesson->videoLesson?->status?->value ?? $lesson->videoLesson?->status ?? 'processing');
                $items[(string) $lesson->id] = [
                    'type' => 'video',
                    'status' => $status,
                ];
            }
            if ($type === LessonType::Scorm->value) {
                $status = (string) ($lesson->scormPackage?->status?->value ?? $lesson->scormPackage?->status ?? 'processing');
                $items[(string) $lesson->id] = [
                    'type' => 'scorm',
                    'status' => $status,
                ];
            }
        }

        return response()->json([
            'ok' => true,
            'items' => $items,
        ]);
    }

    public function storeLesson(Request $request, Module $module)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:200'],
            'type' => ['required', Rule::in(array_map(fn (LessonType $t) => $t->value, LessonType::cases()))],
            'duration_mmss' => ['nullable', 'string', 'max:12'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:59'],
        ]);

        $durationSeconds = $this->durationSecondsFromRequest($request, $data);
        $durationError = null;
        if ($durationSeconds === -1) {
            $durationError = ['duration_seconds' => 'Durata non valida. I secondi devono essere tra 0 e 59.'];
        } elseif (($data['duration_mmss'] ?? '') !== '' && $durationSeconds === null) {
            $durationError = ['duration_mmss' => 'Durata non valida. Usa minuti:secondi (es. 8:03).'];
        }
        if ($durationError) {
            return back()
                ->withErrors($durationError)
                ->withInput();
        }

        $nextPosition = ((int) $module->lessons()->max('position')) + 1;

        $lesson = $module->lessons()->create([
            'title' => $data['title'],
            'type' => $data['type'],
            'position' => $nextPosition,
            'required' => $request->boolean('is_required'),
            'duration_seconds' => $durationSeconds,
        ]);

        $lesson = $lesson->fresh();
        $this->ensureLessonContentRecord($lesson);
        $lesson->load('videoLesson');
        if ($durationSeconds !== null && $lesson->type === LessonType::Video && $lesson->videoLesson) {
            $lesson->videoLesson->update(['duration_seconds' => $durationSeconds]);
        }

        return back()->with('toast', 'Lezione creata.');
    }

    public function updateLesson(Request $request, Module $module, Lesson $lesson)
    {
        abort_unless($lesson->module_id === $module->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:200'],
            'duration_mmss' => ['nullable', 'string', 'max:12'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:59'],
        ]);

        $durationSeconds = $this->durationSecondsFromRequest($request, $data);
        $durationError = null;
        if ($durationSeconds === -1) {
            $durationError = ['duration_seconds' => 'Durata non valida. I secondi devono essere tra 0 e 59.'];
        } elseif (($data['duration_mmss'] ?? '') !== '' && $durationSeconds === null) {
            $durationError = ['duration_mmss' => 'Durata non valida. Usa minuti:secondi (es. 8:03).'];
        }
        if ($durationError) {
            return back()
                ->withErrors($durationError)
                ->withInput();
        }

        $lesson->update([
            'title' => $data['title'],
            'required' => $request->boolean('is_required'),
            'duration_seconds' => $durationSeconds,
        ]);

        $lesson = $lesson->fresh(['videoLesson']);
        $this->ensureLessonContentRecord($lesson);

        if ($durationSeconds !== null && $lesson->type === LessonType::Video && $lesson->videoLesson) {
            $lesson->videoLesson->update(['duration_seconds' => $durationSeconds]);
        }

        return back()->with('toast', 'Lezione aggiornata.');
    }

    public function updateVideoContent(Request $request, Module $module, Lesson $lesson)
    {
        abort_unless($lesson->module_id === $module->id, 404);

        $data = $request->validate([
            'original_s3' => ['nullable', 'string', 'max:2048'],
            'hls_manifest' => ['nullable', 'string', 'max:2048'],
            'manual_status' => ['nullable', Rule::in(array_map(fn (ProcessingStatus $s) => $s->value, ProcessingStatus::cases()))],
        ]);

        $video = $lesson->videoLesson()->firstOrNew();
        $video->fill([
            'lesson_id' => $lesson->id,
        ]);

        if ($request->filled('manual_status')) {
            $video->status = $data['manual_status'];
        }

        if ($request->exists('original_s3')) {
            $raw = (string) ($data['original_s3'] ?? '');
            $video->original_s3 = $raw !== '' ? MediaStorage::normalizeObjectKey($raw) : '';
        }
        if ($request->exists('hls_manifest')) {
            $raw = (string) ($data['hls_manifest'] ?? '');
            $video->hls_manifest = $raw !== '' ? MediaStorage::normalizeObjectKey($raw) : null;
        }

        $video->save();

        $lesson->refresh();
        $lesson->videoLesson?->update(['duration_seconds' => $lesson->duration_seconds]);

        return back()->with('toast', 'Contenuto video aggiornato.');
    }

    public function retryVideoProcessing(Module $module, Lesson $lesson)
    {
        abort_unless($lesson->module_id === $module->id, 404);

        $video = $lesson->videoLesson;
        abort_unless($video, 404);

        $video->update([
            'status' => ProcessingStatus::Processing->value,
        ]);

        ProcessVideoLessonUploadJob::dispatch($video->id, (string) (tenant('id') ?? 'central'));

        return back()->with('toast', 'Retry conversione video avviato.');
    }

    public function updateScormContent(Request $request, Module $module, Lesson $lesson)
    {
        abort_unless($lesson->module_id === $module->id, 404);

        $data = $request->validate([
            's3_path' => ['nullable', 'string', 'max:2048'],
            'version' => ['required', Rule::in(array_map(fn (ScormVersion $v) => $v->value, ScormVersion::cases()))],
            'status' => ['required', Rule::in(array_map(fn (ProcessingStatus $s) => $s->value, ProcessingStatus::cases()))],
        ]);

        $scorm = $lesson->scormPackage()->firstOrNew();
        $pathRaw = (string) ($data['s3_path'] ?? '');
        $scorm->fill([
            'lesson_id' => $lesson->id,
            's3_path' => $pathRaw !== '' ? MediaStorage::normalizeObjectKey($pathRaw) : (string) ($scorm->s3_path ?? ''),
            'version' => $data['version'],
            'status' => $data['status'],
        ]);
        $scorm->save();

        return back()->with('toast', 'Contenuto SCORM aggiornato.');
    }

    public function uploadScormContent(Request $request, Module $module, Lesson $lesson, TenantQuotaService $quota)
    {
        abort_unless($lesson->module_id === $module->id, 404);

        $data = $request->validate([
            'scorm_file' => ['required', 'file', 'mimes:zip'],
            'version' => ['nullable', Rule::in(array_map(fn (ScormVersion $v) => $v->value, ScormVersion::cases()))],
        ]);

        /** @var UploadedFile $file */
        $file = $data['scorm_file'];
        if (! $file->isValid()) {
            return back()->withErrors(['scorm_file' => 'Upload non valido o file troppo grande.']);
        }

        if (! $quota->canAcceptUploadBytes((int) $file->getSize())) {
            return back()->withErrors(['scorm_file' => 'Spazio storage insufficiente per il piano.']);
        }

        $disk = MediaStorage::disk();
        $tenantId = (string) (tenant('id') ?? 'central');
        $relativePath = "tenants/{$tenantId}/scorm-source/".uniqid('scorm_', true).'.zip';
        $storedPath = UploadedFileStorage::put($file, $disk, $relativePath);
        if ($storedPath === false) {
            return back()->withErrors(['scorm_file' => 'Impossibile salvare il file. Riprova.']);
        }

        $scorm = $lesson->scormPackage()->firstOrNew();
        $scorm->fill([
            'lesson_id' => $lesson->id,
            's3_path' => $storedPath,
            'version' => $data['version'] ?? ScormVersion::V12->value,
            'status' => ProcessingStatus::Processing->value,
        ]);
        $scorm->save();

        ProcessScormPackageUploadJob::dispatch($scorm->id, $tenantId);

        return back()->with('toast', 'Upload SCORM avviato: estrazione in coda.');
    }

    public function retryScormProcessing(Module $module, Lesson $lesson)
    {
        abort_unless($lesson->module_id === $module->id, 404);

        $scorm = $lesson->scormPackage;
        abort_unless($scorm, 404);

        $scorm->update([
            'status' => ProcessingStatus::Processing->value,
        ]);

        ProcessScormPackageUploadJob::dispatch($scorm->id, (string) (tenant('id') ?? 'central'));

        return back()->with('toast', 'Retry elaborazione SCORM avviato.');
    }

    public function uploadDocumentContent(Request $request, Module $module, Lesson $lesson, TenantQuotaService $quota)
    {
        abort_unless($lesson->module_id === $module->id, 404);
        abort_unless((string) ($lesson->type?->value ?? $lesson->type) === LessonType::Document->value, 404);

        $data = $request->validate([
            'document_file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
        ]);

        /** @var UploadedFile $file */
        $file = $data['document_file'];
        if (! $file->isValid()) {
            return back()->withErrors(['document_file' => 'Upload non valido o file troppo grande.']);
        }

        if (! $quota->canAcceptUploadBytes((int) $file->getSize())) {
            return back()->withErrors(['document_file' => 'Spazio storage insufficiente per il piano.']);
        }

        $disk = MediaStorage::disk();
        $tenantId = (string) (tenant('id') ?? 'central');
        $relativePath = 'tenants/'.$tenantId.'/documents/'.uniqid('doc_', true).'.pdf';

        $doc = $lesson->documentLesson()->firstOrCreate(
            ['lesson_id' => $lesson->id],
            ['file_path' => null, 'original_filename' => null, 'mime' => null],
        );

        if ($doc->file_path) {
            Storage::disk($disk)->delete($doc->file_path);
        }

        $storedPath = UploadedFileStorage::put($file, $disk, $relativePath);
        if ($storedPath === false) {
            return back()->withErrors(['document_file' => 'Impossibile salvare il file. Riprova.']);
        }

        $doc->update([
            'file_path' => $storedPath,
            'original_filename' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?: 'application/pdf',
        ]);

        return back()->with('toast', 'PDF caricato: visibile ai partecipanti nella pagina lezione.');
    }

    public function destroyLesson(Module $module, Lesson $lesson)
    {
        abort_unless($lesson->module_id === $module->id, 404);
        $lesson->delete();

        return back()->with('toast', 'Lezione eliminata.');
    }

    public function moveLesson(Module $module, Lesson $lesson, string $direction)
    {
        abort_unless($lesson->module_id === $module->id, 404);
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $target = Lesson::query()
            ->where('module_id', $module->id)
            ->when($direction === 'up', fn ($q) => $q->where('position', '<', $lesson->position)->orderByDesc('position'))
            ->when($direction === 'down', fn ($q) => $q->where('position', '>', $lesson->position)->orderBy('position'))
            ->first();

        if ($target) {
            [$lessonPos, $targetPos] = [$lesson->position, $target->position];
            $lesson->update(['position' => $targetPos]);
            $target->update(['position' => $lessonPos]);
        }

        return back();
    }

    private function ensureLessonContentRecord(Lesson $lesson): void
    {
        $type = (string) ($lesson->type?->value ?? $lesson->type);

        if ($type === LessonType::Video->value) {
            VideoLesson::firstOrCreate(
                ['lesson_id' => $lesson->id],
                [
                    'original_s3' => '',
                    'hls_manifest' => null,
                    'duration_seconds' => null,
                    'status' => ProcessingStatus::Processing->value,
                ],
            );
        }

        if ($type === LessonType::Scorm->value) {
            ScormPackage::firstOrCreate(
                ['lesson_id' => $lesson->id],
                [
                    's3_path' => '',
                    'manifest' => null,
                    'version' => ScormVersion::V12->value,
                    'status' => ProcessingStatus::Processing->value,
                ],
            );
        }

        if ($type === LessonType::Document->value) {
            DocumentLesson::firstOrCreate(
                ['lesson_id' => $lesson->id],
                [
                    'file_path' => null,
                    'original_filename' => null,
                    'mime' => null,
                ],
            );
        }
    }
}
