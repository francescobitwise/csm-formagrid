<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonInterface;

/**
 * Il tempo di una lezione SCORM conta solo mentre il filmato avanza.
 * La pagina aperta, o un commit del pacchetto a video fermo, non fa salire la percentuale.
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
        $playback = max(
            $watchedSeconds,
            (int) ($dataModel['__playback_seconds'] ?? 0),
        );

        $delta = 0;
        if ($lastSyncAt !== null) {
            $wall = max(0, min(self::MAX_DELTA_SECONDS, $now->getTimestamp() - $lastSyncAt->getTimestamp()));
            $advanced = max(0, $position - $playback);
            // A timeline that has just finished is no longer "playing": the last
            // seconds would never be stored and the lesson would stay at 99%.
            // Credit only that short tail, still within real time since the last
            // ping. A jump from the start to the end does not qualify.
            $tailAtEnd = $reachedEnd && $advanced > 0 && $advanced <= self::MAX_DELTA_SECONDS;
            if ($mediaPlaying || $tailAtEnd) {
                $delta = (int) min($wall, $advanced);
                $playback += $delta;
            }
        }

        $dataModel['__playback_anchored'] = true;
        $dataModel['__playback_seconds'] = $playback;

        return [
            'watched_seconds' => $playback,
            'delta_seconds' => $delta,
            'last_sync_at' => $now,
            'data_model' => $dataModel,
        ];
    }
}
