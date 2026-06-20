<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PlatformSessionService
{
    public function recordHit(string $userId, Carbon $occurredAt, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        $gapSeconds = max(60, (int) config('analytics.watch_time_session_gap_seconds', 1800));

        $latest = DB::table('platform_sessions')
            ->where('user_id', $userId)
            ->orderByDesc('ended_at')
            ->lockForUpdate()
            ->first();

        $shouldAppend = false;
        $delta = 0;
        $endedAt = null;
        if ($latest && isset($latest->ended_at)) {
            $endedAt = Carbon::parse($latest->ended_at);
            $gap = $occurredAt->getTimestamp() - $endedAt->getTimestamp();
            $shouldAppend = $gap >= 0 && $gap <= $gapSeconds;
            // Cap: avoid counting long background tabs
            $delta = max(0, min(60, $gap));
        }

        if (! $latest || ! $shouldAppend) {
            DB::table('platform_sessions')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'started_at' => $occurredAt,
                'ended_at' => $occurredAt,
                'total_seconds' => 0,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 2000) : null,
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ]);
            return;
        }

        DB::table('platform_sessions')
            ->where('id', $latest->id)
            ->update([
                'ended_at' => $endedAt && $endedAt->greaterThan($occurredAt) ? $latest->ended_at : $occurredAt,
                'total_seconds' => DB::raw('total_seconds + '.(int) $delta),
                'ip_address' => $ipAddress ?: ($latest->ip_address ?? null),
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 2000) : ($latest->user_agent ?? null),
                'updated_at' => $occurredAt,
            ]);
    }
}

