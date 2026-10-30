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
        $anchored = (bool) ($dataModel['__playback_anchored'] ?? false);
        $playback = $anchored
            ? max(0, (int) ($dataModel['__playback_seconds'] ?? $watchedSeconds))
            : min($watchedSeconds, $position);

        $delta = 0;
        if ($mediaPlaying && $lastSyncAt !== null) {
            $wall = max(0, min(self::MAX_DELTA_SECONDS, $now->getTimestamp() - $lastSyncAt->getTimestamp()));
            $advanced = max(0, $position - $playback);
            $delta = (int) min($wall, $advanced);
            $playback += $delta;
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
