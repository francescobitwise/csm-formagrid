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

        $binary = self::ffprobeBinary();
        if ($binary === null) {
            return null;
        }

        try {
            $process = new Process([
                $binary,
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
     * Somma i #EXTINF di una playlist HLS media (.m3u8). Per i master playlist
     * che referenziano sotto-playlist restituisce null (usa il file media).
     */
    public static function hlsPlaylistSeconds(string $m3u8Contents): ?int
    {
        $contents = trim($m3u8Contents);
        if ($contents === '' || ! str_contains($contents, '#EXTINF:')) {
            return null;
        }

        // Master con varianti: niente EXTINF utili sulla durata totale.
        if (preg_match('/#EXT-X-STREAM-INF:/i', $contents) && ! preg_match('/#EXTINF:/i', $contents)) {
            return null;
        }

        $total = 0.0;
        if (! preg_match_all('/#EXTINF\s*:\s*([0-9]+(?:\.[0-9]+)?)/i', $contents, $matches)) {
            return null;
        }

        foreach ($matches[1] as $raw) {
            $total += (float) $raw;
        }

        return $total > 0 ? (int) round($total) : null;
    }

    public static function hlsPlaylistFileSeconds(string $absolutePath): ?int
    {
        if (! is_file($absolutePath)) {
            return null;
        }

        return self::hlsPlaylistSeconds((string) file_get_contents($absolutePath));
    }

    /**
     * Binario ffprobe: MEDIA_FFPROBE_PATH, oppure "ffprobe" se in PATH.
     */
    public static function ffprobeBinary(): ?string
    {
        $configured = trim((string) config('media.ffprobe_path', ''));
        if ($configured !== '') {
            return $configured;
        }

        try {
            $process = Process::fromShellCommandline(
                PHP_OS_FAMILY === 'Windows' ? 'where ffprobe' : 'command -v ffprobe'
            );
            $process->setTimeout(5);
            $process->run();
            if ($process->isSuccessful()) {
                $line = trim(explode("\n", str_replace("\r", '', $process->getOutput()))[0] ?? '');

                return $line !== '' ? $line : 'ffprobe';
            }
        } catch (\Throwable) {
            //
        }

        // Ultimo tentativo: nome nudo (PATH del worker queue).
        return 'ffprobe';
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
