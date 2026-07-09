<?php

declare(strict_types=1);

namespace App\Support;

use Symfony\Component\Process\Process;

/**
 * Rilevazione automatica della durata dei contenuti (video mp4, pacchetti SCORM),
 * così l'admin non deve impostarla a mano.
 */
final class MediaDuration
{
    /**
     * Durata in secondi di un file media locale via ffprobe. Null se ffprobe
     * non è disponibile o il file non è leggibile.
     */
    public static function probeFileSeconds(string $absolutePath): ?int
    {
        if (! is_file($absolutePath)) {
            return null;
        }

        try {
            $process = new Process([
                'ffprobe',
                '-v', 'error',
                '-show_entries', 'format=duration',
                '-of', 'default=noprint_wrappers=1:nokey=1',
                $absolutePath,
            ]);
            $process->setTimeout(60);
            $process->mustRun();

            $out = trim($process->getOutput());

            return is_numeric($out) && (float) $out > 0 ? (int) round((float) $out) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Converte in secondi una durata testuale nei formati usati nei manifest
     * SCORM/LOM: ISO 8601 ("PT50M", "PT1H20M30S") o orologio ("00:50:00", "50:00").
     */
    public static function parseDurationSeconds(string $value): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+(?:\.\d+)?)S)?$/i', $value, $m)) {
            $seconds = ((int) ($m[1] ?? 0)) * 3600
                + ((int) ($m[2] ?? 0)) * 60
                + (int) round((float) ($m[3] ?? 0));

            return $seconds > 0 ? $seconds : null;
        }

        if (preg_match('/^(?:(\d+):)?(\d{1,2}):(\d{2})(?:\.\d+)?$/', $value, $m)) {
            $seconds = ((int) ($m[1] !== '' ? $m[1] : 0)) * 3600
                + ((int) $m[2]) * 60
                + (int) $m[3];

            return $seconds > 0 ? $seconds : null;
        }

        return null;
    }

    /**
     * Estrae la durata dichiarata nel manifest SCORM (LOM typicalLearningTime,
     * presente negli export iSpring/Articulate/Captivate quando configurato).
     */
    public static function scormManifestSeconds(string $manifestAbsolutePath): ?int
    {
        if (! is_file($manifestAbsolutePath)) {
            return null;
        }

        return self::scormManifestXmlSeconds((string) file_get_contents($manifestAbsolutePath));
    }

    public static function scormManifestXmlSeconds(string $xml): ?int
    {
        if ($xml === '') {
            return null;
        }

        // Match namespace-agnostico (imsmd:typicallearningtime, lom:typicalLearningTime, ...)
        if (! preg_match('/<([\w\-]+:)?typicallearningtime[^>]*>(.*?)<\/([\w\-]+:)?typicallearningtime>/is', $xml, $m)) {
            return null;
        }

        $inner = trim(strip_tags($m[2]));

        return $inner !== '' ? self::parseDurationSeconds($inner) : null;
    }
}
