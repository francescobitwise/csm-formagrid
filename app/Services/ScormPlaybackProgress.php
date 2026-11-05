<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonInterface;

/**
 * Quanto tempo è stato visto davvero.
 * Sale solo mentre il playhead avanza, e mai più del tempo reale passato
 * dall'ultimo ping. La fine della timeline non si decide qui: la segnala
 * il player con `__reached_end` e il controller chiude la lezione.
 */
final class ScormPlaybackProgress
{
    /** Massimo accreditabile per un singolo ping, anche se il playhead fa un salto. */
    public const MAX_DELTA_SECONDS = 15;

    /**
     * @param  array<string, mixed>  $dataModel
     * @return array{watched_seconds:int,delta_seconds:int,last_sync_at:?CarbonInterface,data_model:array<string,mixed>}
     */
    public function apply(
        int $watchedSeconds,
        ?CarbonInterface $lastSyncAt,
        array $dataModel,
        CarbonInterface $now,
        bool $mediaPlaying,
        ?int $mediaPosition,
        bool $reachedEnd = false,
        int $playerDuration = 0,
    ): array {
        $watchedSeconds = max(0, $watchedSeconds);

        if ($mediaPosition === null) {
            return [
                'watched_seconds' => $watchedSeconds,
                'delta_seconds' => 0,
                'last_sync_at' => $lastSyncAt,
                'data_model' => $dataModel,
            ];
        }

        $position = max(0, $mediaPosition);
        $playback = max($watchedSeconds, (int) ($dataModel['__playback_seconds'] ?? 0));

        $delta = 0;
        if ($mediaPlaying && $lastSyncAt !== null) {
            $wall = max(0, min(self::MAX_DELTA_SECONDS, $now->getTimestamp() - $lastSyncAt->getTimestamp()));
            $delta = (int) min($wall, max(0, $position - $playback));
            $playback += $delta;
        }

        $dataModel['__playback_anchored'] = true;
        $dataModel['__playback_seconds'] = $playback;
        if ($playerDuration > 0) {
            $dataModel['__player_duration'] = $playerDuration;
        }
        if ($reachedEnd || ($playerDuration > 0 && $position >= ($playerDuration - 1))) {
            $dataModel['__reached_end'] = true;
        }

        return [
            'watched_seconds' => $playback,
            'delta_seconds' => $delta,
            'last_sync_at' => $now,
            'data_model' => $dataModel,
        ];
    }
}
