<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WatchTimeSessionService
{
    public function recordDelta(
        string $enrollmentId,
        string $userId,
        string $courseId,
        string $sourceType,
        int $secondsDelta,
        Carbon $occurredAt,
        ?string $actorUserId = null,
    ): void {
        if ($secondsDelta <= 0) {
            return;
        }

        $gapSeconds = max(60, (int) config('analytics.watch_time_session_gap_seconds', 1800));

        $latest = DB::table('watch_time_sessions')
            ->where('enrollment_id', $enrollmentId)
            ->orderByDesc('ended_at')
            ->lockForUpdate()
            ->first();

        $shouldAppend = false;
        if ($latest && isset($latest->ended_at)) {
            $endedAt = Carbon::parse($latest->ended_at);
            $gap = $occurredAt->getTimestamp() - $endedAt->getTimestamp();
            $shouldAppend = $gap >= 0 && $gap <= $gapSeconds;
        }

        if (! $latest || ! $shouldAppend) {
            $id = (string) Str::uuid();
            $video = $sourceType === 'video' ? $secondsDelta : 0;
            $scorm = $sourceType === 'scorm' ? $secondsDelta : 0;
            DB::table('watch_time_sessions')->insert([
                'id' => $id,
                'enrollment_id' => $enrollmentId,
                'user_id' => $userId,
                'course_id' => $courseId,
                'started_at' => $occurredAt,
                'ended_at' => $occurredAt,
                'video_seconds' => $video,
                'scorm_seconds' => $scorm,
                'total_seconds' => $secondsDelta,
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
            'updated_by_user_id' => $actorUserId,
            'updated_at' => $occurredAt,
        ];

        if ($sourceType === 'video') {
            $updates['video_seconds'] = DB::raw('video_seconds + '.(int) $secondsDelta);
        } elseif ($sourceType === 'scorm') {
            $updates['scorm_seconds'] = DB::raw('scorm_seconds + '.(int) $secondsDelta);
        }

        DB::table('watch_time_sessions')
            ->where('id', $latest->id)
            ->update($updates);
    }
}

