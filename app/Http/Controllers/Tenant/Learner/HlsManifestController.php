<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Learner;

use App\Enums\EnrollmentStatus;
use App\Enums\LessonType;
use App\Enums\ProcessingStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\Lesson;
use App\Models\Tenant\User;
use App\Models\Tenant\VideoLesson;
use App\Services\CourseScheduleService;
use App\Services\LessonSequentialAccessService;
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * HLS learner: manifest autenticato; segmenti via CDN (prod) o proxy same-origin (locale).
 */
final class HlsManifestController extends Controller
{
    public function __construct(
        private readonly LessonSequentialAccessService $lessonSequentialAccess,
        private readonly CourseScheduleService $courseSchedule,
    ) {}

    public function __invoke(Request $request, Course $course, Lesson $lesson): SymfonyResponse
    {
        $resolved = $this->authorizeHls($request->user(), $course, $lesson);
        abort_unless($resolved !== null, 404);

        [, $manifestKey, $disk] = $resolved;

        $body = (string) $disk->get($manifestKey);
        $rewritten = $this->rewritePlaylist($body, $manifestKey, $course, $lesson);

        return response($rewritten, 200, [
            'Content-Type' => 'application/vnd.apple.mpegurl; charset=utf-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function file(Request $request, Course $course, Lesson $lesson, string $file): StreamedResponse|SymfonyResponse
    {
        $resolved = $this->authorizeHls($request->user(), $course, $lesson);
        abort_unless($resolved !== null, 404);

        [, $manifestKey, $disk] = $resolved;
        $dir = $this->hlsDirectory($manifestKey);
        $key = $this->resolveRelativeObjectKey($dir, $file);
        abort_unless($key !== null && $this->isRewritablePlaylistRef($file), 404);
        abort_unless($disk->exists($key), 404);

        if (str_ends_with(strtolower($file), '.m3u8')) {
            $body = (string) $disk->get($key);
            $rewritten = $this->rewritePlaylist($body, $key, $course, $lesson);

            return response($rewritten, 200, [
                'Content-Type' => 'application/vnd.apple.mpegurl; charset=utf-8',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        $stream = $disk->readStream($key);
        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $this->mimeForHlsFile($key),
            'Cache-Control' => 'private, max-age=60',
        ]);
    }

    /**
     * @return array{0: VideoLesson, 1: string, 2: \Illuminate\Contracts\Filesystem\Filesystem}|null
     */
    private function authorizeHls(?User $user, Course $course, Lesson $lesson): ?array
    {
        if ($user === null) {
            return null;
        }

        if (! $course->modules()->where('modules.id', $lesson->module_id)->exists()) {
            return null;
        }

        if (! $course->hasStarted()) {
            return null;
        }

        if (! $this->courseSchedule->isOpenFor($course, $user)) {
            return null;
        }

        $enrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->first();

        if ($enrollment === null) {
            return null;
        }

        if (! $this->lessonSequentialAccess->canAccessLesson($course, $lesson, $enrollment, $user)) {
            return null;
        }

        if ($lesson->type !== LessonType::Video) {
            return null;
        }

        $lesson->loadMissing('videoLesson');
        $video = $lesson->videoLesson;
        if ($video === null || $video->status !== ProcessingStatus::Ready) {
            return null;
        }

        $manifestKey = $video->hls_manifest;
        if (! is_string($manifestKey) || $manifestKey === '') {
            return null;
        }

        $diskName = MediaStorage::disk();
        if (config("filesystems.disks.{$diskName}.driver") !== 's3') {
            return null;
        }

        $disk = Storage::disk($diskName);
        if (! $disk->exists($manifestKey)) {
            return null;
        }

        return [$video, $manifestKey, $disk];
    }

    /**
     * auto = locale → proxy; altrimenti CDN se AWS_URL è configurato, senno proxy.
     * proxy = sempre same-origin (affidabile, più carico app).
     * cdn  = URL pubblici CloudFront/AWS_URL (leggero; serve CORS ok sul CDN).
     */
    private function segmentDeliveryMode(): string
    {
        $mode = strtolower(trim((string) config('media.hls_segment_delivery', 'auto')));
        if (! in_array($mode, ['auto', 'proxy', 'cdn'], true)) {
            $mode = 'auto';
        }

        if ($mode === 'auto') {
            if (app()->environment(['local', 'development', 'dev'])) {
                return 'proxy';
            }

            return $this->cdnBaseUrl() !== null ? 'cdn' : 'proxy';
        }

        if ($mode === 'cdn' && $this->cdnBaseUrl() === null) {
            return 'proxy';
        }

        return $mode;
    }

    private function cdnBaseUrl(): ?string
    {
        $disk = MediaStorage::disk();
        $cdn = config("filesystems.disks.{$disk}.url");
        if (! is_string($cdn) || trim($cdn) === '') {
            return null;
        }

        return rtrim($cdn, '/');
    }

    private function rewritePlaylist(string $body, string $playlistKey, Course $course, Lesson $lesson): string
    {
        $dir = $this->hlsDirectory($playlistKey);
        $mode = $this->segmentDeliveryMode();
        $lines = preg_split('/\r\n|\r|\n/', $body) ?: [];
        $out = [];

        foreach ($lines as $line) {
            $trim = rtrim($line, "\r\n");
            $t = trim($trim);

            if ($t === '' || str_starts_with($t, '#')) {
                if (str_contains($t, 'URI="') && preg_match('/URI="([^"]+)"/', $t, $m)) {
                    $uri = $m[1];
                    $absolute = $this->absoluteMediaUrl($mode, $course, $lesson, $dir, $uri);
                    if ($absolute !== null) {
                        $escaped = str_replace('"', '%22', $absolute);
                        $out[] = (string) preg_replace('/URI="[^"]+"/', 'URI="'.$escaped.'"', $trim, 1);

                        continue;
                    }
                }
                $out[] = $trim;

                continue;
            }

            if (str_contains($t, '://')) {
                $out[] = $trim;

                continue;
            }

            $absolute = $this->absoluteMediaUrl($mode, $course, $lesson, $dir, $t);
            $out[] = $absolute ?? $trim;
        }

        return implode("\n", $out);
    }

    private function absoluteMediaUrl(string $mode, Course $course, Lesson $lesson, string $dir, string $ref): ?string
    {
        $key = $this->resolveRelativeObjectKey($dir, $ref);
        if ($key === null || ! $this->isRewritablePlaylistRef($ref)) {
            return null;
        }

        if ($mode === 'cdn') {
            $cdn = $this->cdnBaseUrl();

            return $cdn !== null ? $cdn.'/'.ltrim($key, '/') : null;
        }

        return $this->proxyHlsUrl($course, $lesson, $dir, $key, $ref);
    }

    private function proxyHlsUrl(Course $course, Lesson $lesson, string $dir, string $key, string $ref): string
    {
        $relative = $dir !== '' && str_starts_with($key, $dir.'/')
            ? substr($key, strlen($dir) + 1)
            : basename($key);

        $refNorm = ltrim(str_replace('\\', '/', $ref), '/');
        if (str_contains($refNorm, '/')) {
            $relative = $refNorm;
        }

        return route('tenant.learner.hls.file', [
            'course' => $course,
            'lesson' => $lesson,
            'file' => $relative,
        ], absolute: true);
    }

    private function hlsDirectory(string $manifestKey): string
    {
        return trim(dirname($manifestKey), '/.');
    }

    private function resolveRelativeObjectKey(string $dir, string $ref): ?string
    {
        $ref = trim(str_replace('\\', '/', $ref));
        if ($ref === '' || str_contains($ref, '://') || str_contains($ref, '..')) {
            return null;
        }

        $ref = ltrim($ref, '/');
        if ($ref === '' || strlen($ref) > 512) {
            return null;
        }

        if (! preg_match('/^[a-zA-Z0-9._\-\/]+$/', $ref)) {
            return null;
        }

        if ($dir === '' || $dir === '.') {
            return $ref;
        }

        return $dir.'/'.$ref;
    }

    private function isRewritablePlaylistRef(string $ref): bool
    {
        $base = basename(str_replace('\\', '/', $ref));

        return (bool) preg_match('/\.(m3u8|ts|m4s|aac|mp4|vtt)$/i', $base);
    }

    private function mimeForHlsFile(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'm3u8' => 'application/vnd.apple.mpegurl',
            'ts' => 'video/mp2t',
            'm4s' => 'video/iso.segment',
            'mp4' => 'video/mp4',
            'aac' => 'audio/aac',
            'vtt' => 'text/vtt',
            default => 'application/octet-stream',
        };
    }
}
