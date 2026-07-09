<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant\Lesson;
use App\Models\Tenant\ScormPackage;
use App\Models\Tenant\VideoLesson;
use App\Support\MediaDuration;
use App\Support\MediaStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Backfill della durata per contenuti già caricati (i nuovi upload la rilevano
 * automaticamente nei job di elaborazione). Eseguire nel contesto tenant:
 *
 *   php artisan tenants:run content:detect-durations
 *   php artisan tenants:run content:detect-durations --option="probe-video=1"
 */
class DetectLessonDurationsCommand extends Command
{
    protected $signature = 'content:detect-durations {--probe-video= : Scarica anche i file video da S3 e usa ffprobe (più lento)}';

    protected $description = 'Rileva la durata delle lezioni da manifest SCORM e file video già caricati';

    public function handle(): int
    {
        $probeVideo = (bool) $this->option('probe-video');

        $this->backfillScorm($probeVideo);
        $this->backfillVideo($probeVideo);

        return self::SUCCESS;
    }

    private function backfillScorm(bool $probeVideo): void
    {
        $packages = ScormPackage::query()
            ->whereHas('lesson', fn ($q) => $q->whereNull('duration_seconds'))
            ->get();

        foreach ($packages as $package) {
            $seconds = $this->scormDurationSeconds($package, $probeVideo);

            if ($seconds === null) {
                $this->line("SCORM {$package->id}: durata non rilevabile");

                continue;
            }

            $manifest = is_array($package->manifest) ? $package->manifest : [];
            $manifest['duration_seconds'] = $seconds;
            $package->update(['manifest' => $manifest]);

            Lesson::query()
                ->whereKey($package->lesson_id)
                ->whereNull('duration_seconds')
                ->update(['duration_seconds' => $seconds]);

            $this->info("SCORM {$package->id}: {$seconds}s");
        }
    }

    private function scormDurationSeconds(ScormPackage $package, bool $probeVideo): ?int
    {
        $manifest = is_array($package->manifest) ? $package->manifest : [];

        if (is_numeric($manifest['duration_seconds'] ?? null) && (int) $manifest['duration_seconds'] > 0) {
            return (int) $manifest['duration_seconds'];
        }

        $disk = MediaStorage::disk();
        $baseDir = trim(dirname((string) $package->s3_path), '/');
        $manifestRel = (string) ($manifest['manifest_path'] ?? 'imsmanifest.xml');

        // launch_path può stare in una sottocartella: risali fino alla radice del pacchetto.
        $candidates = array_unique([
            $baseDir.'/'.$manifestRel,
            trim(dirname($baseDir), '/').'/'.$manifestRel,
        ]);

        foreach ($candidates as $key) {
            if (! Storage::disk($disk)->exists($key)) {
                continue;
            }

            $seconds = MediaDuration::scormManifestXmlSeconds((string) Storage::disk($disk)->get($key));
            if ($seconds !== null) {
                return $seconds;
            }

            if ($probeVideo) {
                return $this->probeLargestRemoteVideo($disk, trim(dirname($key), '/'));
            }

            return null;
        }

        return null;
    }

    private function backfillVideo(bool $probeVideo): void
    {
        if (! $probeVideo) {
            return;
        }

        $videos = VideoLesson::query()
            ->whereNull('duration_seconds')
            ->whereNotNull('original_s3')
            ->where('original_s3', '!=', '')
            ->get();

        $disk = MediaStorage::disk();

        foreach ($videos as $video) {
            if (strtolower(pathinfo((string) $video->original_s3, PATHINFO_EXTENSION)) !== 'mp4') {
                continue;
            }

            $seconds = $this->probeRemoteFile($disk, (string) $video->original_s3);
            if ($seconds === null) {
                $this->line("Video {$video->id}: durata non rilevabile");

                continue;
            }

            $video->update(['duration_seconds' => $seconds]);
            Lesson::query()
                ->whereKey($video->lesson_id)
                ->whereNull('duration_seconds')
                ->update(['duration_seconds' => $seconds]);

            $this->info("Video {$video->id}: {$seconds}s");
        }
    }

    private function probeLargestRemoteVideo(string $disk, string $prefix): ?int
    {
        $best = null;
        $bestSize = 0;

        foreach (Storage::disk($disk)->allFiles($prefix) as $key) {
            $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
            if (! in_array($ext, ['mp4', 'webm', 'm4v', 'mov'], true)) {
                continue;
            }

            $size = (int) Storage::disk($disk)->size($key);
            if ($size > $bestSize) {
                $bestSize = $size;
                $best = $key;
            }
        }

        return $best !== null ? $this->probeRemoteFile($disk, $best) : null;
    }

    private function probeRemoteFile(string $disk, string $key): ?int
    {
        $tmp = storage_path('app/tmp/duration-probe-'.uniqid().'.'.pathinfo($key, PATHINFO_EXTENSION));
        @mkdir(dirname($tmp), 0777, true);

        try {
            file_put_contents($tmp, Storage::disk($disk)->get($key));

            return MediaDuration::probeFileSeconds($tmp);
        } catch (\Throwable) {
            return null;
        } finally {
            @unlink($tmp);
        }
    }
}
