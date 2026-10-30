<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\Lesson;
use App\Models\Tenant\ScormPackage;
use App\Models\Tenant\ScormTracking;
use App\Models\Tenant\User;
use App\Services\EnrollmentProgressService;
use App\Services\LessonSequentialAccessService;
use App\Services\ScormPlaybackProgress;
use App\Services\WatchTimeSessionService;
use BackedEnum;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class ScormTrackingController extends Controller
{
    /**
     * Completamento calcolato da noi: se il pacchetto non comunica nulla (o è un
     * semplice video), la lezione viene marcata completata quando il tempo di
     * visione tracciato lato server raggiunge questa quota della durata dichiarata.
     */
    private const WATCH_TIME_COMPLETION_RATIO = 0.95;

    public function __construct(
        private readonly EnrollmentProgressService $enrollmentProgressService,
        private readonly WatchTimeSessionService $watchTimeSessionService,
        private readonly LessonSequentialAccessService $lessonSequentialAccess,
        private readonly ScormPlaybackProgress $scormPlaybackProgress,
    ) {}

    public function update(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'data' => ['required', 'array'],
            'media_duration_seconds' => ['nullable', 'integer', 'min:1', 'max:86400'],
            'media_playing' => ['nullable', 'boolean'],
            'media_position_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ]);

        $context = $this->resolveContext($request);
        if ($context instanceof JsonResponse) {
            return $context;
        }

        ['user' => $user, 'enrollment' => $enrollment, 'package' => $package, 'lesson' => $lesson] = $context;

        $course = Course::query()->whereKey($enrollment->course_id)->first();
        if ($course === null || ! $this->lessonSequentialAccess->canAccessLesson($course, $lesson, $enrollment, $user)) {
            return response()->json(['message' => 'Completa le lezioni precedenti prima di proseguire.'], 403);
        }

        $cmi = $payload['data'];
        $mediaPlaying = (bool) ($payload['media_playing'] ?? false);
        $mediaPosition = isset($payload['media_position_seconds'])
            ? (int) $payload['media_position_seconds']
            : null;

        $tracking = DB::connection()->transaction(function () use ($user, $cmi, $enrollment, $package, $mediaPlaying, $mediaPosition) {
            $row = $this->lockTracking($user, $enrollment, $package);

            if ($row !== null) {
                return $this->persistTracking($row, $cmi, $user, $enrollment, $package, $mediaPlaying, $mediaPosition);
            }

            try {
                return $this->persistTracking(null, $cmi, $user, $enrollment, $package, $mediaPlaying, $mediaPosition);
            } catch (UniqueConstraintViolationException) {
                // Race sulla creazione: un'altra richiesta ha inserito la riga.
                return $this->persistTracking($this->lockTracking($user, $enrollment, $package), $cmi, $user, $enrollment, $package, $mediaPlaying, $mediaPosition);
            }
        });

        $this->rememberObservedDuration($lesson, (int) ($payload['media_duration_seconds'] ?? 0));

        $tracking = $this->applyWatchTimeCompletion($tracking, $lesson);

        $enrollment->refresh();
        $this->enrollmentProgressService->refresh($enrollment);

        return response()->json(['ok' => true, 'id' => $tracking->id]);
    }

    public function status(Request $request): JsonResponse
    {
        $context = $this->resolveContext($request);
        if ($context instanceof JsonResponse) {
            return $context;
        }

        ['user' => $user, 'enrollment' => $enrollment, 'package' => $package, 'lesson' => $lesson] = $context;

        $row = ScormTracking::query()
            ->where('user_id', $user->id)
            ->where('scorm_package_id', $package->id)
            ->where('enrollment_id', $enrollment->id)
            ->first(['id', 'status', 'watched_seconds', 'last_sync_at', 'updated_at', 'data_model']);

        if ($row === null) {
            return response()->json([
                'ok' => true,
                'exists' => false,
                'status' => 'not_attempted',
                'watched_seconds' => 0,
                'last_sync_at' => null,
            ]);
        }

        $status = $this->statusValue($row);

        return response()->json([
            'ok' => true,
            'exists' => true,
            'status' => $status,
            'progress_pct' => $this->progressPct($status, $row, $lesson),
            'watched_seconds' => (int) ($row->watched_seconds ?? 0),
            'last_sync_at' => $row->last_sync_at?->toIso8601String(),
            'updated_at' => $row->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Risolve e autorizza utente, iscrizione, pacchetto e lezione condivisi
     * dai due endpoint. Ritorna una JsonResponse d'errore se qualcosa non torna.
     *
     * @return array{user:User,enrollment:Enrollment,package:ScormPackage,lesson:Lesson}|JsonResponse
     */
    private function resolveContext(Request $request): array|JsonResponse
    {
        $data = $request->validate([
            'package_id' => ['required', 'uuid'],
            'enrollment_id' => ['required', 'uuid'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $enrollment = Enrollment::query()
            ->whereKey($data['enrollment_id'])
            ->where('user_id', $user->id)
            ->first();

        if ($enrollment === null) {
            return response()->json(['message' => 'Iscrizione non valida.'], 403);
        }

        $package = ScormPackage::query()
            ->whereKey($data['package_id'])
            ->first(['id', 'lesson_id']);

        if ($package === null) {
            return response()->json(['message' => 'Pacchetto SCORM non trovato.'], 404);
        }

        $lessonInCourse = Course::query()
            ->whereKey($enrollment->course_id)
            ->whereHas('lessons', fn ($q) => $q->whereKey($package->lesson_id))
            ->exists();

        $lesson = $lessonInCourse ? Lesson::query()->whereKey($package->lesson_id)->first() : null;
        if ($lesson === null) {
            return response()->json(['message' => 'Accesso negato a questa lezione.'], 403);
        }

        return compact('user', 'enrollment', 'package', 'lesson');
    }

    private function lockTracking(User $user, Enrollment $enrollment, ScormPackage $package): ?ScormTracking
    {
        return ScormTracking::query()
            ->where('user_id', $user->id)
            ->where('scorm_package_id', $package->id)
            ->where('enrollment_id', $enrollment->id)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Crea o aggiorna la riga di tracking e registra il delta di tempo visione.
     *
     * @param  array<string, mixed>  $cmi
     */
    private function persistTracking(
        ?ScormTracking $row,
        array $cmi,
        User $user,
        Enrollment $enrollment,
        ScormPackage $package,
        bool $mediaPlaying = false,
        ?int $mediaPosition = null,
    ): ScormTracking {
        $built = $this->buildTrackingAttributes($row, $cmi, $mediaPlaying, $mediaPosition);

        if ($row === null) {
            $tracking = ScormTracking::query()->create(array_merge([
                'user_id' => $user->id,
                'scorm_package_id' => $package->id,
                'enrollment_id' => $enrollment->id,
            ], $built['attributes']));
        } else {
            $row->update($built['attributes']);
            $tracking = $row->fresh() ?? $row;
        }

        $this->recordSessionDeltaIfAny(
            $enrollment,
            (string) $user->id,
            (int) $built['delta_seconds'],
            (string) $built['event'],
            (string) $package->lesson_id,
        );

        return $tracking;
    }

    /**
     * Completamento basato sul tempo di visione tracciato lato server: copre i
     * pacchetti SCORM che non comunicano mai `completed` (o che incapsulano un
     * semplice video). Richiede una durata della lezione impostata dall'admin.
     */
    private function applyWatchTimeCompletion(ScormTracking $tracking, Lesson $lesson): ScormTracking
    {
        if (in_array($this->statusValue($tracking), ['completed', 'passed'], true)) {
            return $tracking;
        }

        $model = is_array($tracking->data_model) ? $tracking->data_model : [];
        if (empty($model['__playback_anchored'])) {
            return $tracking;
        }

        $duration = $this->lessonDurationSeconds($lesson);
        $threshold = (int) max(1, floor($duration * self::WATCH_TIME_COMPLETION_RATIO));
        if ($duration <= 0 || (int) ($tracking->watched_seconds ?? 0) < $threshold) {
            return $tracking;
        }

        $model['__completed_by_watch_time'] = true;

        $tracking->update([
            'status' => 'completed',
            'data_model' => $model,
        ]);

        return $tracking->fresh() ?? $tracking;
    }

    /**
     * Percentuale mostrata nel badge: quella dichiarata dal pacchetto
     * (cmi.progress_measure) oppure, in mancanza, tempo visto / durata lezione.
     */
    private function progressPct(string $status, ScormTracking $row, Lesson $lesson): ?int
    {
        if (in_array(strtolower(trim($status)), ['completed', 'passed'], true)) {
            return 100;
        }

        $model = is_array($row->data_model) ? $row->data_model : [];
        if (! empty($model['__playback_anchored'])) {
            $duration = $this->lessonDurationSeconds($lesson);
            $playback = (int) ($model['__playback_seconds'] ?? $row->watched_seconds ?? 0);
            if ($duration > 0) {
                return (int) min(99, floor(($playback / $duration) * 100));
            }
        }

        $raw = $model['cmi.progress_measure'] ?? null;
        if (is_numeric($raw)) {
            $pct = (float) $raw <= 1.0 ? (float) $raw * 100 : (float) $raw;
            if ($pct <= 100.0) {
                return (int) max(0, min(100, round($pct)));
            }
        }

        $duration = $this->lessonDurationSeconds($lesson);
        if ($duration > 0) {
            return (int) min(99, floor(((int) ($row->watched_seconds ?? 0) / $duration) * 100));
        }

        return null;
    }

    /**
     * Se il player interno comunica una durata e in anagrafica manca o è più corta,
     * la lezione adotta la durata più lunga vista. Il completamento al 95% usa quel valore.
     */
    private function rememberObservedDuration(Lesson $lesson, int $observed): void
    {
        if ($observed < 60) {
            return;
        }

        if ($observed <= (int) ($lesson->duration_seconds ?? 0)) {
            return;
        }

        Lesson::query()->whereKey($lesson->id)->update(['duration_seconds' => $observed]);
        $lesson->duration_seconds = $observed;
    }

    /**
     * Durata usata per percentuale e completamento: quella della lezione, oppure
     * quella rilevata nel pacchetto se in anagrafica è ancora vuota.
     */
    private function lessonDurationSeconds(Lesson $lesson): int
    {
        $duration = (int) ($lesson->duration_seconds ?? 0);
        if ($duration > 0) {
            return $duration;
        }

        $lesson->loadMissing('scormPackage');

        return max(0, (int) data_get($lesson->scormPackage?->manifest, 'duration_seconds', 0));
    }

    /**
     * @param  array<string, mixed>  $incomingCmi
     * @return array{attributes:array<string,mixed>,delta_seconds:int,event:string}
     */
    private function buildTrackingAttributes(
        ?ScormTracking $tracking,
        array $incomingCmi,
        bool $mediaPlaying = false,
        ?int $mediaPosition = null,
    ): array {
        $incomingForModel = $incomingCmi;
        if (($incomingForModel['__event'] ?? '') === 'ping') {
            unset($incomingForModel['__event']);
        }

        $merged = array_merge($tracking?->data_model ?? [], $incomingForModel);

        $rawStatus = $incomingCmi['cmi.core.lesson_status']
            ?? $incomingCmi['cmi.completion_status']
            ?? $incomingCmi['cmi.success_status']
            ?? ($tracking !== null ? $this->statusValue($tracking) : null)
            ?? 'incomplete';

        $score = $incomingCmi['cmi.core.score.raw']
            ?? $incomingCmi['cmi.score.raw']
            ?? null;

        $suspend = $incomingCmi['cmi.suspend_data']
            ?? $merged['cmi.suspend_data']
            ?? null;

        $event = isset($incomingCmi['__event']) ? (string) $incomingCmi['__event'] : '';
        $time = $this->scormPlaybackProgress->apply(
            (int) ($tracking?->watched_seconds ?? 0),
            $tracking?->last_sync_at,
            $merged,
            Date::now(),
            $mediaPlaying,
            $mediaPosition,
        );

        return [
            'attributes' => [
                'data_model' => $time['data_model'],
                'status' => $this->normalizeStatus((string) $rawStatus),
                'score' => is_numeric($score) ? (float) $score : null,
                'suspend_data' => $suspend !== null && $suspend !== '' ? (string) $suspend : null,
                'watched_seconds' => $time['watched_seconds'],
                'last_sync_at' => $time['last_sync_at'] ?? $tracking?->last_sync_at,
            ],
            'delta_seconds' => (int) $time['delta_seconds'],
            'event' => $event,
        ];
    }

    private function statusValue(ScormTracking $tracking): string
    {
        return $tracking->status instanceof BackedEnum ? $tracking->status->value : (string) $tracking->status;
    }

    private function normalizeStatus(string $raw): string
    {
        return match (strtolower(trim($raw))) {
            'not attempted', 'not_attempted' => 'not_attempted',
            'completed' => 'completed',
            'passed' => 'passed',
            'failed' => 'failed',
            default => 'incomplete',
        };
    }

    private function recordSessionDeltaIfAny(Enrollment $enrollment, string $userId, int $deltaSeconds, string $event, ?string $lessonId = null): void
    {
        if ($deltaSeconds <= 0 || $event === 'initialize') {
            return;
        }

        $this->watchTimeSessionService->recordDelta(
            enrollmentId: (string) $enrollment->id,
            userId: $userId,
            courseId: (string) $enrollment->course_id,
            lessonId: $lessonId,
            sourceType: 'scorm',
            secondsDelta: $deltaSeconds,
            occurredAt: Date::now(),
            ipAddress: request()?->ip(),
            userAgent: request()?->userAgent(),
        );
    }
}
