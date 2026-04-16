<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\ScormPackage;
use App\Models\Tenant\ScormTracking;
use App\Services\EnrollmentProgressService;
use App\Services\WatchTimeSessionService;
use BackedEnum;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;

class ScormTrackingController extends Controller
{
    public function __construct(
        private readonly EnrollmentProgressService $enrollmentProgressService,
        private readonly WatchTimeSessionService $watchTimeSessionService,
    ) {}

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'package_id' => ['required', 'uuid'],
            'enrollment_id' => ['required', 'uuid'],
            'data' => ['required', 'array'],
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
            ->whereHas('lessons', function ($q) use ($package) {
                $q->whereKey($package->lesson_id);
            })
            ->exists();

        if (! $lessonInCourse) {
            return response()->json(['message' => 'Accesso negato a questa lezione.'], 403);
        }

        $tracking = DB::connection()->transaction(function () use ($user, $data, $enrollment) {
            $row = ScormTracking::query()
                ->where('user_id', $user->id)
                ->where('scorm_package_id', $data['package_id'])
                ->where('enrollment_id', $data['enrollment_id'])
                ->lockForUpdate()
                ->first();

            $built = $this->buildTrackingAttributes($row, $data['data']);
            $attributes = $built['attributes'];

            if ($row === null) {
                try {
                    $created = ScormTracking::query()->create(array_merge([
                        'user_id' => $user->id,
                        'scorm_package_id' => $data['package_id'],
                        'enrollment_id' => $data['enrollment_id'],
                    ], $attributes));
                    $this->recordSessionDeltaIfAny($enrollment, (string) $user->id, (int) $built['delta_seconds'], (string) $built['event']);
                    return $created;
                } catch (UniqueConstraintViolationException) {
                    $row = ScormTracking::query()
                        ->where('user_id', $user->id)
                        ->where('scorm_package_id', $data['package_id'])
                        ->where('enrollment_id', $data['enrollment_id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    $built = $this->buildTrackingAttributes($row, $data['data']);
                    $attributes = $built['attributes'];
                    $row->update($attributes);
                    $fresh = $row->fresh();
                    $this->recordSessionDeltaIfAny($enrollment, (string) $user->id, (int) $built['delta_seconds'], (string) $built['event']);
                    return $fresh;
                }
            }

            $row->update($attributes);
            $fresh = $row->fresh();
            $this->recordSessionDeltaIfAny($enrollment, (string) $user->id, (int) $built['delta_seconds'], (string) $built['event']);
            return $fresh;
        });

        $enrollment->refresh();
        $this->enrollmentProgressService->refresh($enrollment);

        return response()->json(['ok' => true, 'id' => $tracking->id]);
    }

    public function status(Request $request): JsonResponse
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
            ->whereHas('lessons', function ($q) use ($package) {
                $q->whereKey($package->lesson_id);
            })
            ->exists();

        if (! $lessonInCourse) {
            return response()->json(['message' => 'Accesso negato a questa lezione.'], 403);
        }

        $row = ScormTracking::query()
            ->where('user_id', $user->id)
            ->where('scorm_package_id', $data['package_id'])
            ->where('enrollment_id', $data['enrollment_id'])
            ->first(['id', 'status', 'watched_seconds', 'last_sync_at', 'updated_at', 'data_model']);

        if (! $row) {
            return response()->json([
                'ok' => true,
                'exists' => false,
                'status' => 'not_attempted',
                'watched_seconds' => 0,
                'last_sync_at' => null,
            ]);
        }

        $status = $row->status instanceof BackedEnum ? $row->status->value : (string) $row->status;
        $progressPct = $this->extractProgressPct($status, is_array($row->data_model) ? $row->data_model : null);

        return response()->json([
            'ok' => true,
            'exists' => true,
            'status' => $status,
            'progress_pct' => $progressPct,
            'watched_seconds' => (int) ($row->watched_seconds ?? 0),
            'last_sync_at' => $row->last_sync_at?->toIso8601String(),
            'updated_at' => $row->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $model
     */
    private function extractProgressPct(string $status, ?array $model): ?int
    {
        $st = strtolower(trim($status));
        if (in_array($st, ['completed', 'passed'], true)) {
            return 100;
        }
        if ($model === null) {
            return null;
        }

        $candidates = [
            $model['cmi.progress_measure'] ?? null,     // SCORM 2004 (0..1)
            $model['cmi.completion_threshold'] ?? null, // sometimes
            $model['cmi.score.scaled'] ?? null,         // 0..1
        ];

        foreach ($candidates as $raw) {
            if (! is_numeric($raw)) {
                continue;
            }
            $v = (float) $raw;
            if ($v <= 1.0) {
                return (int) max(0, min(100, round($v * 100)));
            }
            if ($v <= 100.0) {
                return (int) max(0, min(100, round($v)));
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $incomingCmi
     */
    /**
     * @param  array<string, mixed>  $incomingCmi
     * @return array{attributes:array<string,mixed>,delta_seconds:int,event:string}
     */
    private function buildTrackingAttributes(?ScormTracking $tracking, array $incomingCmi): array
    {
        $incomingForModel = $incomingCmi;
        if (($incomingForModel['__event'] ?? '') === 'ping') {
            unset($incomingForModel['__event']);
        }

        $merged = array_merge($tracking?->data_model ?? [], $incomingForModel);

        $previousStatus = $tracking?->status;
        if ($previousStatus instanceof BackedEnum) {
            $previousStatus = $previousStatus->value;
        } else {
            $previousStatus = $previousStatus !== null ? (string) $previousStatus : null;
        }

        $rawStatus = $incomingCmi['cmi.core.lesson_status']
            ?? $incomingCmi['cmi.completion_status']
            ?? $incomingCmi['cmi.success_status']
            ?? $previousStatus
            ?? 'incomplete';

        $status = $this->normalizeStatus((string) $rawStatus);

        $score = $incomingCmi['cmi.core.score.raw']
            ?? $incomingCmi['cmi.score.raw']
            ?? null;

        $suspend = $incomingCmi['cmi.suspend_data']
            ?? $merged['cmi.suspend_data']
            ?? null;

        $now = Date::now();
        $time = $this->watchTimeAttributes($tracking, $incomingCmi, $now);

        return [
            'attributes' => [
                'data_model' => $merged,
                'status' => $status,
                'score' => is_numeric($score) ? (float) $score : null,
                'suspend_data' => $suspend !== null && $suspend !== '' ? (string) $suspend : null,
                'watched_seconds' => $time['watched_seconds'],
                'last_sync_at' => $time['last_sync_at'],
            ],
            'delta_seconds' => (int) $time['delta_seconds'],
            'event' => (string) $time['event'],
        ];
    }

    /**
     * Calcolo "watch time" lato server, indipendente dai cmi.*.
     *
     * @param  array<string, mixed>  $incomingCmi
     * @return array{watched_seconds:int,last_sync_at:\Illuminate\Support\CarbonInterface,delta_seconds:int,event:string}
     */
    private function watchTimeAttributes(?ScormTracking $tracking, array $incomingCmi, $now): array
    {
        $current = (int) ($tracking?->watched_seconds ?? 0);
        $last = $tracking?->last_sync_at;

        $event = isset($incomingCmi['__event']) ? (string) $incomingCmi['__event'] : '';
        $isInit = $event === 'initialize';

        $delta = 0;
        if (! $isInit && $last) {
            // Calcolo robusto su timestamp (evita edge-case di cast/string).
            $nowTs = method_exists($now, 'getTimestamp') ? (int) $now->getTimestamp() : time();
            $lastTs = method_exists($last, 'getTimestamp') ? (int) $last->getTimestamp() : $nowTs;
            $raw = $nowTs - $lastTs;
            // Cap anti-abuso / tab in background: massimo 15s per ping.
            $delta = max(0, min(15, $raw));
        }

        return [
            'watched_seconds' => $current + $delta,
            'last_sync_at' => $now,
            'delta_seconds' => $delta,
            'event' => $event,
        ];
    }

    private function normalizeStatus(string $raw): string
    {
        $status = strtolower(trim($raw));

        return match ($status) {
            'not attempted', 'not_attempted' => 'not_attempted',
            'incomplete', 'browsed', 'unknown' => 'incomplete',
            'completed' => 'completed',
            'passed' => 'passed',
            'failed' => 'failed',
            default => 'incomplete',
        };
    }

    private function recordSessionDeltaIfAny(Enrollment $enrollment, string $userId, int $deltaSeconds, string $event): void
    {
        if ($deltaSeconds <= 0 || $event === 'initialize') {
            return;
        }

        $this->watchTimeSessionService->recordDelta(
            enrollmentId: (string) $enrollment->id,
            userId: $userId,
            courseId: (string) $enrollment->course_id,
            sourceType: 'scorm',
            secondsDelta: $deltaSeconds,
            occurredAt: Date::now(),
        );
    }
}
