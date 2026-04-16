<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\VideoProgress;
use Carbon\CarbonInterface;

/**
 * Solo lezioni **video** (HLS). Le lezioni **SCORM** sono tracciate da `ScormTrackingController`
 * / `ScormTracking` (modello `cmi.*` del runtime); non applicare questa classe a SCORM.
 */
final class VideoProgressIntegrityService
{
    /** Velocità massima "plausibile" rispetto al tempo di parete (es. 1.35 ≈ 1.35x). */
    private const MAX_PLAYBACK_RATE_VS_WALL = 1.35;

    /** Buffer fisso in secondi (jitter di rete / arrotondamenti). */
    private const BUFFER_SECONDS = 4;

    /** Massimo incremento in un solo sync anche con pause lunghe (anti-salto dopo assenza). */
    private const MAX_SINGLE_JUMP_SECONDS = 48;

    /** Oltre questi secondi di pausa, il tempo efficace per il calcolo è plafonato. */
    private const MAX_ELAPSED_WALL_SECONDS = 90;

    /** Tra due sync non si accetta avanzamento in avanti se è passato meno di questo tempo (ms). */
    private const MIN_INTERVAL_MS_BETWEEN_FORWARD_MS = 1800;

    /** Primo sync dopo creazione riga: tetto generoso solo per avviare la riproduzione. */
    private const FIRST_FORWARD_MAX_SECONDS = 24;

    public function capForwardProgress(
        VideoProgress $progress,
        int $clientLastPosition,
        int $clientWatchedSeconds,
        ?int $catalogDurationSeconds,
        CarbonInterface $now,
    ): array {
        $duration = $catalogDurationSeconds !== null && $catalogDurationSeconds > 0
            ? $catalogDurationSeconds
            : null;

        $clientLastPosition = $this->clampToDuration($clientLastPosition, $duration);
        $clientWatchedSeconds = $this->clampToDuration($clientWatchedSeconds, $duration);

        $serverPos = (int) ($progress->last_position ?? 0);
        $serverWatched = (int) ($progress->watched_seconds ?? 0);

        $lastSync = $progress->last_sync_at ?? $progress->updated_at;

        if ($clientLastPosition <= $serverPos && $clientWatchedSeconds <= $serverWatched) {
            return [
                'last_position' => max($serverPos, $clientLastPosition),
                'watched_seconds' => max($serverWatched, $clientWatchedSeconds),
            ];
        }

        $isFirstTrustedSync = $progress->last_sync_at === null;

        if (! $isFirstTrustedSync && $lastSync !== null) {
            $msSince = (int) $now->diffInMilliseconds($lastSync);
            if ($msSince < self::MIN_INTERVAL_MS_BETWEEN_FORWARD_MS
                && ($clientLastPosition > $serverPos || $clientWatchedSeconds > $serverWatched)) {
                return [
                    'last_position' => $serverPos,
                    'watched_seconds' => $serverWatched,
                ];
            }
        }

        $elapsedWall = 0.0;
        if ($lastSync !== null) {
            $elapsedWall = min(
                (float) self::MAX_ELAPSED_WALL_SECONDS,
                max(0.0, $now->diffInMilliseconds($lastSync) / 1000.0)
            );
        }

        if ($isFirstTrustedSync) {
            $maxForward = self::FIRST_FORWARD_MAX_SECONDS;
        } else {
            $maxForward = min(
                self::MAX_SINGLE_JUMP_SECONDS,
                (int) floor($elapsedWall * self::MAX_PLAYBACK_RATE_VS_WALL) + self::BUFFER_SECONDS
            );
            $maxForward = max(0, $maxForward);
        }

        $cappedPos = min($clientLastPosition, $serverPos + $maxForward);
        $cappedWatched = min($clientWatchedSeconds, $serverWatched + $maxForward);

        $cappedPos = max($serverPos, $cappedPos);
        $cappedWatched = max($serverWatched, $cappedWatched);

        if ($duration !== null) {
            $cappedPos = min($cappedPos, $duration);
            $cappedWatched = min($cappedWatched, $duration);
        }

        return [
            'last_position' => $cappedPos,
            'watched_seconds' => $cappedWatched,
        ];
    }

    public function canMarkCompleted(
        int $cappedLastPosition,
        int $cappedWatchedSeconds,
        ?int $durationSeconds,
        bool $clientClaimsComplete,
    ): bool {
        if (! $clientClaimsComplete) {
            return false;
        }

        if ($durationSeconds === null || $durationSeconds <= 0) {
            return false;
        }

        $threshold = (int) max(1, floor($durationSeconds * 0.96));

        return max($cappedLastPosition, $cappedWatchedSeconds) >= $threshold;
    }

    private function clampToDuration(int $value, ?int $duration): int
    {
        if ($duration === null) {
            return max(0, $value);
        }

        return max(0, min($value, $duration));
    }
}
