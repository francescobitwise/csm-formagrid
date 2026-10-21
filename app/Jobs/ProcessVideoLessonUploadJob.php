<?php

namespace App\Jobs;

use App\Enums\ProcessingStatus;
use App\Models\Tenant\Lesson;
use App\Models\Tenant\VideoLesson;
use App\Support\MediaDuration;
use App\Support\MediaStorage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ProcessVideoLessonUploadJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $videoLessonId, public string $tenantId) {}

    public function handle(): void
    {
        $video = VideoLesson::query()->find($this->videoLessonId);
        if (! $video || ! $video->original_s3) {
            return;
        }

        $disk = MediaStorage::disk();
        $path = $video->original_s3;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'm3u8') {
            $durationSeconds = $this->durationFromRemoteHls($disk, ltrim($path, '/'));
            $update = [
                'hls_manifest' => ltrim($path, '/'),
                'status' => ProcessingStatus::Ready->value,
            ];
            if ($durationSeconds !== null) {
                $update['duration_seconds'] = $durationSeconds;
            }
            $video->update($update);
            $this->syncLessonDuration($video, $durationSeconds);

            return;
        }

        if ($ext !== 'mp4') {
            $video->update([
                'hls_manifest' => ltrim($path, '/'),
                'status' => ProcessingStatus::Ready->value,
            ]);

            return;
        }

        $tmpRoot = storage_path('app/tmp/video-'.$video->id.'-'.uniqid());
        $sourcePath = $tmpRoot.'/source.mp4';
        $outDir = $tmpRoot.'/out';
        @mkdir($outDir, 0777, true);

        try {
            file_put_contents($sourcePath, Storage::disk($disk)->get($path));

            $durationSeconds = MediaDuration::probeFileSeconds($sourcePath);

            $process = new Process([
                'ffmpeg',
                '-y',
                '-i',
                $sourcePath,
                '-codec:v',
                'libx264',
                '-codec:a',
                'aac',
                '-hls_time',
                '6',
                '-hls_playlist_type',
                'vod',
                '-hls_segment_filename',
                $outDir.'/segment_%03d.ts',
                $outDir.'/master.m3u8',
            ]);
            $process->setTimeout(1800);
            $process->mustRun();

            if ($durationSeconds === null) {
                $durationSeconds = $this->durationFromLocalHlsDir($outDir);
            }

            $targetBase = "tenants/{$this->tenantId}/video-hls/{$video->id}";
            $putOpts = MediaStorage::putOptionsForDisk($disk);
            $files = glob($outDir.'/*') ?: [];
            foreach ($files as $file) {
                $name = basename($file);
                Storage::disk($disk)->put("{$targetBase}/{$name}", file_get_contents($file), $putOpts);
            }

            $posterRel = "tenants/{$this->tenantId}/video-posters/{$video->id}.jpg";
            $posterPathToSet = $this->tryExtractAndStorePoster($sourcePath, $tmpRoot, $disk, $posterRel, $putOpts);

            $update = [
                'hls_manifest' => "{$targetBase}/master.m3u8",
                'status' => ProcessingStatus::Ready->value,
            ];
            if ($posterPathToSet !== null) {
                $update['poster_path'] = $posterPathToSet;
            }
            if ($durationSeconds !== null) {
                $update['duration_seconds'] = $durationSeconds;
            }

            $video->update($update);
            $this->syncLessonDuration($video, $durationSeconds);
        } catch (\Throwable $e) {
            Log::error('Video processing failed', [
                'video_lesson_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            $video->update([
                'status' => ProcessingStatus::Error->value,
            ]);
        } finally {
            $this->cleanupDir($tmpRoot);
        }
    }

    /**
     * Durata dal media: aggiorna sempre la lezione (niente inserimento manuale).
     */
    private function syncLessonDuration(VideoLesson $video, ?int $durationSeconds): void
    {
        if ($durationSeconds === null || $durationSeconds <= 0 || $video->lesson_id === null) {
            return;
        }

        Lesson::query()
            ->whereKey($video->lesson_id)
            ->update(['duration_seconds' => $durationSeconds]);
    }

    private function durationFromLocalHlsDir(string $outDir): ?int
    {
        $master = $outDir.'/master.m3u8';
        $fromMaster = MediaDuration::hlsPlaylistFileSeconds($master);
        if ($fromMaster !== null) {
            return $fromMaster;
        }

        foreach (glob($outDir.'/*.m3u8') ?: [] as $playlist) {
            $seconds = MediaDuration::hlsPlaylistFileSeconds($playlist);
            if ($seconds !== null) {
                return $seconds;
            }
        }

        return null;
    }

    private function durationFromRemoteHls(string $disk, string $manifestKey): ?int
    {
        try {
            if (! Storage::disk($disk)->exists($manifestKey)) {
                return null;
            }

            $contents = (string) Storage::disk($disk)->get($manifestKey);
            $direct = MediaDuration::hlsPlaylistSeconds($contents);
            if ($direct !== null) {
                return $direct;
            }

            // Master → prima variante media.
            if (preg_match('/^[^#].+\.m3u8\s*$/mi', $contents, $m)) {
                $child = trim($m[0]);
                $childKey = str_contains($child, '/')
                    ? ltrim($child, '/')
                    : trim(dirname($manifestKey), '/.').'/'.$child;
                $childKey = ltrim(str_replace('\\', '/', $childKey), '/');
                if (Storage::disk($disk)->exists($childKey)) {
                    return MediaDuration::hlsPlaylistSeconds((string) Storage::disk($disk)->get($childKey));
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function cleanupDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $item) {
            $full = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($full)) {
                $this->cleanupDir($full);
            } else {
                @unlink($full);
            }
        }

        @rmdir($dir);
    }

    /**
     * Estrae un frame dal MP4 e lo salva sul disco media. In caso di errore restituisce null
     * (il video HLS resta comunque valido; in retry si mantiene l'anteprima precedente se presente).
     */
    private function tryExtractAndStorePoster(
        string $sourcePath,
        string $tmpRoot,
        string $disk,
        string $posterRel,
        array $putOpts
    ): ?string {
        $posterTmp = $tmpRoot.'/poster.jpg';

        foreach (['1', '0'] as $ss) {
            try {
                @unlink($posterTmp);
                $posterProcess = new Process([
                    'ffmpeg',
                    '-y',
                    '-ss',
                    $ss,
                    '-i',
                    $sourcePath,
                    '-frames:v',
                    '1',
                    '-q:v',
                    '3',
                    $posterTmp,
                ]);
                $posterProcess->setTimeout(120);
                $posterProcess->mustRun();

                if (is_file($posterTmp) && filesize($posterTmp) > 0) {
                    Storage::disk($disk)->put($posterRel, (string) file_get_contents($posterTmp), $putOpts);

                    return $posterRel;
                }
            } catch (\Throwable $e) {
                Log::warning('Video poster extraction attempt failed', [
                    'video_lesson_id' => $this->videoLessonId,
                    'ss' => $ss,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }
}
