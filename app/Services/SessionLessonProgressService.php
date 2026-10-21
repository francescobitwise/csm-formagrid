<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LessonType;
use App\Enums\ScormTrackingStatus;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\Lesson;
use App\Models\Tenant\ScormTracking;
use App\Models\Tenant\VideoProgress;
use Illuminate\Support\Facades\DB;

/**
 * Allinea il completamento delle lezioni al tempo registrato nelle sessioni.
 * La percentuale del corso conta le lezioni obbligatorie completate, non i minuti in sé.
 */
final class SessionLessonProgressService
{
    private const COMPLETION_RATIO = 0.96;

    public function __construct(
        private readonly EnrollmentProgressService $enrollmentProgressService,
    ) {}

    /**
     * @param  list<string|null>  $lessonIds
     * @return list<string>
     */
    public function sync(Enrollment $enrollment, array $lessonIds): array
    {
        $ids = array_values(array_unique(array_filter(
            $lessonIds,
            fn ($id) => is_string($id) && $id !== ''
        )));

        if ($ids === []) {
            return [];
        }

        $lessons = Lesson::query()
            ->with([
                'videoLesson:id,lesson_id,duration_seconds',
                'scormPackage:id,lesson_id',
            ])
            ->whereIn('id', $ids)
            ->get();

        $notes = [];
        foreach ($lessons as $lesson) {
            $note = $this->syncLesson($enrollment, $lesson);
            if ($note !== null) {
                $notes[] = $note;
            }
        }

        $this->enrollmentProgressService->refresh($enrollment);

        return $notes;
    }

    public function creditsCoverLesson(int $creditedSeconds, ?int $durationSeconds): bool
    {
        if ($creditedSeconds <= 0) {
            return false;
        }

        if ($durationSeconds === null || $durationSeconds <= 0) {
            return true;
        }

        return $creditedSeconds >= $this->threshold($durationSeconds);
    }

    private function syncLesson(Enrollment $enrollment, Lesson $lesson): ?string
    {
        $type = (string) ($lesson->type?->value ?? $lesson->type);
        $credited = (int) DB::table('watch_time_sessions')
            ->where('enrollment_id', $enrollment->id)
            ->where('lesson_id', $lesson->id)
            ->sum('total_seconds');
        $duration = $this->durationSeconds($lesson);
        $covers = $this->creditsCoverLesson($credited, $duration);

        if ($type === LessonType::Video->value) {
            $videoLessonId = $lesson->videoLesson?->id;
            if ($videoLessonId === null) {
                return '«'.$lesson->title.'» non ha un video collegato, quindi la percentuale non cambia.';
            }

            $progress = VideoProgress::query()
                ->where('user_id', $enrollment->user_id)
                ->where('video_lesson_id', $videoLessonId)
                ->where('enrollment_id', $enrollment->id)
                ->first();

            $playerHolds = $this->playerHoldsVideo($progress, $duration);
            $this->writeVideoCompletion($enrollment, (string) $videoLessonId, $progress, $covers || $playerHolds, $duration);

            return $this->pendingNote($lesson->title, $credited, $duration, $covers || $playerHolds);
        }

        if ($type === LessonType::Scorm->value) {
            $packageId = $lesson->scormPackage?->id;
            if ($packageId === null) {
                return '«'.$lesson->title.'» non ha un pacchetto SCORM collegato, quindi la percentuale non cambia.';
            }

            $tracking = ScormTracking::query()
                ->where('user_id', $enrollment->user_id)
                ->where('scorm_package_id', $packageId)
                ->where('enrollment_id', $enrollment->id)
                ->first();

            $playerHolds = $this->playerHoldsScorm($tracking, $duration);
            $this->writeScormCompletion($enrollment, (string) $packageId, $tracking, $covers || $playerHolds);

            return $this->pendingNote($lesson->title, $credited, $duration, $covers || $playerHolds);
        }

        return '«'.$lesson->title.'» non entra nel calcolo della percentuale del corso.';
    }

    private function writeVideoCompletion(Enrollment $enrollment, string $videoLessonId, ?VideoProgress $progress, bool $complete, ?int $duration): void
    {
        if ($progress === null) {
            if (! $complete) {
                return;
            }

            VideoProgress::query()->create([
                'user_id' => $enrollment->user_id,
                'video_lesson_id' => $videoLessonId,
                'enrollment_id' => $enrollment->id,
                'watched_seconds' => 0,
                'last_position' => 0,
                'duration_seconds' => $duration,
                'completed' => true,
            ]);

            return;
        }

        if ((bool) $progress->completed === $complete) {
            return;
        }

        $progress->completed = $complete;
        if ($progress->duration_seconds === null && $duration !== null) {
            $progress->duration_seconds = $duration;
        }
        $progress->save();
    }

    private function writeScormCompletion(Enrollment $enrollment, string $packageId, ?ScormTracking $tracking, bool $complete): void
    {
        $status = $complete ? ScormTrackingStatus::Completed : ScormTrackingStatus::Incomplete;

        if ($tracking === null) {
            if (! $complete) {
                return;
            }

            ScormTracking::query()->create([
                'user_id' => $enrollment->user_id,
                'scorm_package_id' => $packageId,
                'enrollment_id' => $enrollment->id,
                'status' => $status,
                'watched_seconds' => 0,
            ]);

            return;
        }

        $current = $tracking->status instanceof ScormTrackingStatus
            ? $tracking->status
            : ScormTrackingStatus::tryFrom((string) $tracking->status);

        if ($current === ScormTrackingStatus::Passed) {
            return;
        }

        if ($current === $status) {
            return;
        }

        $tracking->status = $status;
        $tracking->save();
    }

    private function playerHoldsVideo(?VideoProgress $progress, ?int $duration): bool
    {
        if ($progress === null || ! $progress->completed || $duration === null || $duration <= 0) {
            return false;
        }

        return max((int) $progress->watched_seconds, (int) $progress->last_position) >= $this->threshold($duration);
    }

    private function playerHoldsScorm(?ScormTracking $tracking, ?int $duration): bool
    {
        if ($tracking === null || $duration === null || $duration <= 0) {
            return false;
        }

        $status = $tracking->status instanceof ScormTrackingStatus
            ? $tracking->status
            : ScormTrackingStatus::tryFrom((string) $tracking->status);

        if ($status === ScormTrackingStatus::Passed) {
            return true;
        }

        return $status === ScormTrackingStatus::Completed
            && (int) $tracking->watched_seconds >= $this->threshold($duration);
    }

    private function pendingNote(string $title, int $creditedSeconds, ?int $durationSeconds, bool $complete): ?string
    {
        if ($complete || $durationSeconds === null || $durationSeconds <= 0) {
            return null;
        }

        $missingSeconds = max(0, $this->threshold($durationSeconds) - $creditedSeconds);
        $missingMinutes = (int) ceil($missingSeconds / 60);

        return 'Mancano '.$missingMinutes.' minuti su «'.$title.'» per far avanzare la percentuale.';
    }

    private function durationSeconds(Lesson $lesson): ?int
    {
        $seconds = $lesson->duration_seconds;
        if (($seconds === null || $seconds <= 0) && $lesson->videoLesson?->duration_seconds) {
            $seconds = (int) $lesson->videoLesson->duration_seconds;
        }

        $seconds = (int) ($seconds ?? 0);

        return $seconds > 0 ? $seconds : null;
    }

    private function threshold(int $durationSeconds): int
    {
        return (int) max(1, floor($durationSeconds * self::COMPLETION_RATIO));
    }
}
