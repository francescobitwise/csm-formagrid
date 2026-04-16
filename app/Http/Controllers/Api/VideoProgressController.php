<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\VideoLesson;
use App\Models\Tenant\VideoProgress;
use App\Services\EnrollmentProgressService;
use App\Services\VideoProgressIntegrityService;
use App\Services\WatchTimeSessionService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VideoProgressController extends Controller
{
    public function __construct(
        private readonly EnrollmentProgressService $enrollmentProgressService,
        private readonly VideoProgressIntegrityService $videoProgressIntegrityService,
        private readonly WatchTimeSessionService $watchTimeSessionService,
    ) {}

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'video_lesson_id' => ['required', 'uuid'],
            'enrollment_id' => ['required', 'uuid'],
            'watched_seconds' => ['nullable', 'integer', 'min:0'],
            'last_position' => ['nullable', 'integer', 'min:0'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'completed' => ['nullable', 'boolean'],
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

        $videoLesson = VideoLesson::query()
            ->whereKey($data['video_lesson_id'])
            ->with('lesson:id,duration_seconds')
            ->first(['id', 'duration_seconds', 'lesson_id']);

        if ($videoLesson === null) {
            return response()->json(['message' => 'Lezione video non trovata.'], 404);
        }

        $lessonInCourse = Course::query()
            ->whereKey($enrollment->course_id)
            ->whereHas('lessons', function ($q) use ($videoLesson) {
                $q->whereKey($videoLesson->lesson_id);
            })
            ->exists();

        if (! $lessonInCourse) {
            return response()->json(['message' => 'Accesso negato a questa lezione.'], 403);
        }

        $rawCatalog = $videoLesson->duration_seconds ?? $videoLesson->lesson?->duration_seconds;
        $catalogDuration = is_numeric($rawCatalog) ? max(0, (int) $rawCatalog) : 0;
        $catalogDuration = $catalogDuration > 0 ? $catalogDuration : null;

        $clientDuration = isset($data['duration_seconds']) && $data['duration_seconds'] !== null
            ? (int) $data['duration_seconds']
            : null;
        $effectiveDuration = $catalogDuration ?? $clientDuration;

        $clientLastPosition = isset($data['last_position'])
            ? (int) $data['last_position']
            : (isset($data['watched_seconds']) ? (int) $data['watched_seconds'] : 0);
        $clientWatched = isset($data['watched_seconds'])
            ? (int) $data['watched_seconds']
            : $clientLastPosition;

        $clientClaimsComplete = array_key_exists('completed', $data) && (bool) $data['completed'];

        $payload = DB::connection()->transaction(function () use (
            $user,
            $data,
            $effectiveDuration,
            $catalogDuration,
            $clientLastPosition,
            $clientWatched,
            $clientClaimsComplete,
            $enrollment,
        ) {
            $progress = VideoProgress::query()
                ->where('user_id', $user->id)
                ->where('video_lesson_id', $data['video_lesson_id'])
                ->where('enrollment_id', $data['enrollment_id'])
                ->lockForUpdate()
                ->first();

            if ($progress === null) {
                try {
                    $progress = VideoProgress::query()->create([
                        'user_id' => $user->id,
                        'video_lesson_id' => $data['video_lesson_id'],
                        'enrollment_id' => $data['enrollment_id'],
                        'watched_seconds' => 0,
                        'last_position' => 0,
                        'completed' => false,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    $progress = VideoProgress::query()
                        ->where('user_id', $user->id)
                        ->where('video_lesson_id', $data['video_lesson_id'])
                        ->where('enrollment_id', $data['enrollment_id'])
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            $beforeWatched = (int) ($progress->watched_seconds ?? 0);
            $wasCompleted = (bool) $progress->completed;

            $capped = $this->videoProgressIntegrityService->capForwardProgress(
                $progress,
                $clientLastPosition,
                $clientWatched,
                $effectiveDuration,
                now(),
            );

            $progress->last_position = $capped['last_position'];
            $progress->watched_seconds = $capped['watched_seconds'];

            if ($catalogDuration !== null) {
                $progress->duration_seconds = $catalogDuration;
            } elseif ($progress->duration_seconds === null && $effectiveDuration !== null) {
                $progress->duration_seconds = $effectiveDuration;
            }

            $canComplete = $this->videoProgressIntegrityService->canMarkCompleted(
                $capped['last_position'],
                $capped['watched_seconds'],
                $effectiveDuration,
                $clientClaimsComplete,
            );

            if ($wasCompleted) {
                $progress->completed = true;
            } elseif ($clientClaimsComplete) {
                $progress->completed = $canComplete;
            } else {
                $progress->completed = $wasCompleted;
            }

            if ($progress->completed && $effectiveDuration !== null && $effectiveDuration > 0) {
                $progress->duration_seconds = $effectiveDuration;
                $progress->last_position = max((int) $progress->last_position, $effectiveDuration);
                $progress->watched_seconds = max((int) $progress->watched_seconds, $effectiveDuration);
            }

            $progress->last_sync_at = now();
            $progress->save();

            $afterWatched = (int) ($progress->watched_seconds ?? 0);
            $delta = max(0, $afterWatched - $beforeWatched);
            if ($delta > 0) {
                $this->watchTimeSessionService->recordDelta(
                    enrollmentId: (string) $enrollment->id,
                    userId: (string) $user->id,
                    courseId: (string) $enrollment->course_id,
                    sourceType: 'video',
                    secondsDelta: $delta,
                    occurredAt: now(),
                );
            }

            return ['id' => $progress->id];
        });

        $enrollment->refresh();
        $this->enrollmentProgressService->refresh($enrollment);

        return response()->json(['ok' => true, 'id' => $payload['id']]);
    }

    public function status(Request $request): JsonResponse
    {
        $data = $request->validate([
            'video_lesson_id' => ['required', 'uuid'],
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

        $videoLesson = VideoLesson::query()
            ->whereKey($data['video_lesson_id'])
            ->first(['id', 'lesson_id']);

        if ($videoLesson === null) {
            return response()->json(['message' => 'Lezione video non trovata.'], 404);
        }

        $lessonInCourse = Course::query()
            ->whereKey($enrollment->course_id)
            ->whereHas('lessons', function ($q) use ($videoLesson) {
                $q->whereKey($videoLesson->lesson_id);
            })
            ->exists();

        if (! $lessonInCourse) {
            return response()->json(['message' => 'Accesso negato a questa lezione.'], 403);
        }

        $row = VideoProgress::query()
            ->where('user_id', $user->id)
            ->where('video_lesson_id', $data['video_lesson_id'])
            ->where('enrollment_id', $data['enrollment_id'])
            ->first(['id', 'watched_seconds', 'completed', 'last_sync_at', 'updated_at']);

        if (! $row) {
            return response()->json([
                'ok' => true,
                'exists' => false,
                'completed' => false,
                'watched_seconds' => 0,
                'last_sync_at' => null,
            ]);
        }

        return response()->json([
            'ok' => true,
            'exists' => true,
            'completed' => (bool) ($row->completed ?? false),
            'watched_seconds' => (int) ($row->watched_seconds ?? 0),
            'last_sync_at' => $row->last_sync_at?->toIso8601String(),
            'updated_at' => $row->updated_at?->toIso8601String(),
        ]);
    }
}
