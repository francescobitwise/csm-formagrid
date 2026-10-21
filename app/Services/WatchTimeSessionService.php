<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WatchTimeSessionService
{
    /**
     * Apre o allunga la sessione della lezione visibile. Il primo ping registra
     * l'accesso; i successivi accreditano il tempo di permanenza, al massimo 15 secondi.
     */
    public function recordVisibleHit(
        string $enrollmentId,
        string $userId,
        string $courseId,
        string $lessonId,
        string $sourceType,
        Carbon $occurredAt,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
        $gapSeconds = max(1, (int) config('analytics.watch_time_active_session_gap_seconds', 90));

        DB::transaction(function () use ($enrollmentId, $userId, $courseId, $lessonId, $sourceType, $occurredAt, $ipAddress, $userAgent, $gapSeconds): void {
            $latest = DB::table('watch_time_sessions')
                ->where('enrollment_id', $enrollmentId)
                ->orderByDesc('ended_at')
                ->lockForUpdate()
                ->first();

            $delta = 0;
            if ($latest && isset($latest->ended_at)) {
                $gap = $occurredAt->getTimestamp() - Carbon::parse($latest->ended_at)->getTimestamp();
                $sameLesson = (string) ($latest->lesson_id ?? '') === $lessonId;
                if ($sameLesson && $gap >= 0 && $gap <= $gapSeconds) {
                    if ($gap === 0) {
                        return;
                    }
                    $delta = min(15, $gap);
                }
            }

            $this->recordDelta(
                enrollmentId: $enrollmentId,
                userId: $userId,
                courseId: $courseId,
                lessonId: $lessonId,
                sourceType: $sourceType,
                secondsDelta: $delta,
                occurredAt: $occurredAt,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );
        });
    }

    public function recordDelta(
        string $enrollmentId,
        string $userId,
        string $courseId,
        ?string $lessonId,
        string $sourceType,
        int $secondsDelta,
        Carbon $occurredAt,
        ?string $actorUserId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
        if ($secondsDelta < 0) {
            return;
        }

        $gapSeconds = max(1, (int) config('analytics.watch_time_active_session_gap_seconds', 90));

        $latest = DB::table('watch_time_sessions')
            ->where('enrollment_id', $enrollmentId)
            ->orderByDesc('ended_at')
            ->lockForUpdate()
            ->first();

        $shouldAppend = false;
        if ($latest && isset($latest->ended_at)) {
            $endedAt = Carbon::parse($latest->ended_at);
            $gap = $occurredAt->getTimestamp() - $endedAt->getTimestamp();
            $sameLesson = (string) ($latest->lesson_id ?? '') === (string) ($lessonId ?? '');
            $shouldAppend = $sameLesson && $gap >= 0 && $gap <= $gapSeconds;
        }

        if ($secondsDelta === 0 && $shouldAppend) {
            return;
        }

        if (! $latest || ! $shouldAppend) {
            $id = (string) Str::uuid();
            $video = $sourceType === 'video' ? $secondsDelta : 0;
            $scorm = $sourceType === 'scorm' ? $secondsDelta : 0;
            $document = $sourceType === 'document' ? $secondsDelta : 0;
            DB::table('watch_time_sessions')->insert([
                'id' => $id,
                'enrollment_id' => $enrollmentId,
                'user_id' => $userId,
                'course_id' => $courseId,
                'lesson_id' => $lessonId,
                'started_at' => $occurredAt,
                'ended_at' => $occurredAt,
                'video_seconds' => $video,
                'scorm_seconds' => $scorm,
                'document_seconds' => $document,
                'total_seconds' => $secondsDelta,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 2000) : null,
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ]);

            return;
        }

        $updates = [
            'ended_at' => Carbon::parse($latest->ended_at)->greaterThan($occurredAt) ? $latest->ended_at : $occurredAt,
            'total_seconds' => DB::raw('total_seconds + '.(int) $secondsDelta),
            'lesson_id' => $lessonId ?? $latest->lesson_id ?? null,
            'ip_address' => $ipAddress ?: ($latest->ip_address ?? null),
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 2000) : ($latest->user_agent ?? null),
            'updated_by_user_id' => $actorUserId,
            'updated_at' => $occurredAt,
        ];

        if ($sourceType === 'video') {
            $updates['video_seconds'] = DB::raw('video_seconds + '.(int) $secondsDelta);
        } elseif ($sourceType === 'scorm') {
            $updates['scorm_seconds'] = DB::raw('scorm_seconds + '.(int) $secondsDelta);
        } elseif ($sourceType === 'document') {
            $updates['document_seconds'] = DB::raw('document_seconds + '.(int) $secondsDelta);
        }

        DB::table('watch_time_sessions')
            ->where('id', $latest->id)
            ->update($updates);
    }

    public function createManual(
        string $enrollmentId,
        string $userId,
        string $courseId,
        ?string $lessonId,
        Carbon $startedAt,
        Carbon $endedAt,
        int $videoSeconds,
        int $scormSeconds,
        ?string $actorUserId,
    ): string {
        $id = (string) Str::uuid();
        $now = now();

        DB::table('watch_time_sessions')->insert([
            'id' => $id,
            'enrollment_id' => $enrollmentId,
            'user_id' => $userId,
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'video_seconds' => max(0, $videoSeconds),
            'scorm_seconds' => max(0, $scormSeconds),
            'total_seconds' => max(0, $videoSeconds) + max(0, $scormSeconds),
            'ip_address' => null,
            'user_agent' => 'manual',
            'created_by_user_id' => $actorUserId,
            'updated_by_user_id' => $actorUserId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
    }

    public function updateManual(
        string $sessionId,
        string $enrollmentId,
        string $courseId,
        ?string $lessonId,
        Carbon $startedAt,
        Carbon $endedAt,
        int $videoSeconds,
        int $scormSeconds,
        ?string $actorUserId,
    ): bool {
        $current = DB::table('watch_time_sessions')
            ->where('id', $sessionId)
            ->where('enrollment_id', $enrollmentId)
            ->where('course_id', $courseId)
            ->first();

        if (! $current) {
            return false;
        }

        $videoSeconds = $this->keepUnchangedRemainder(max(0, $videoSeconds), (int) $current->video_seconds);
        $scormSeconds = $this->keepUnchangedRemainder(max(0, $scormSeconds), (int) $current->scorm_seconds);
        $documentSeconds = (int) ($current->document_seconds ?? 0);

        DB::table('watch_time_sessions')
            ->where('id', $sessionId)
            ->where('enrollment_id', $enrollmentId)
            ->where('course_id', $courseId)
            ->update([
                'lesson_id' => $lessonId,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'video_seconds' => $videoSeconds,
                'scorm_seconds' => $scormSeconds,
                'total_seconds' => $videoSeconds + $scormSeconds + $documentSeconds,
                'updated_by_user_id' => $actorUserId,
                'updated_at' => now(),
            ]);

        return true;
    }

    private function keepUnchangedRemainder(int $submittedSeconds, int $previousSeconds): int
    {
        if (intdiv($previousSeconds, 60) === intdiv($submittedSeconds, 60)) {
            return $previousSeconds;
        }

        return $submittedSeconds;
    }

    public function deleteManual(string $sessionId, string $enrollmentId, string $courseId): bool
    {
        $deleted = DB::table('watch_time_sessions')
            ->where('id', $sessionId)
            ->where('enrollment_id', $enrollmentId)
            ->where('course_id', $courseId)
            ->delete();

        return $deleted > 0;
    }
}
