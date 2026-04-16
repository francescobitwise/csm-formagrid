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
use App\Support\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class HlsManifestController extends Controller
{
    public function __invoke(Request $request, Course $course, Lesson $lesson): SymfonyResponse
    {
        abort_unless(
            $course->modules()->where('modules.id', $lesson->module_id)->exists(),
            404
        );

        $user = $request->user();
        abort_unless($user !== null, 401);

        $enrolled = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->exists();

        abort_unless($enrolled, 403);

        abort_unless($lesson->type === LessonType::Video, 404);

        $lesson->loadMissing('videoLesson');
        $video = $lesson->videoLesson;
        abort_unless($video !== null && $video->status === ProcessingStatus::Ready, 404);

        $manifestKey = $video->hls_manifest;
        abort_unless(is_string($manifestKey) && $manifestKey !== '', 404);

        $diskName = MediaStorage::disk();
        abort_unless(config("filesystems.disks.{$diskName}.driver") === 's3', 404);

        $disk = Storage::disk($diskName);
        abort_unless($disk->exists($manifestKey), 404);

        $ttl = max(1, min(240, (int) config('media.signed_hls_ttl_minutes', 90)));
        $expires = now()->addMinutes($ttl);
        $body = (string) $disk->get($manifestKey);
        $rewritten = $this->rewritePlaylistWithPresignedSegments($body, $manifestKey, $diskName, $expires);

        return response($rewritten, 200, [
            'Content-Type' => 'application/vnd.apple.mpegurl; charset=utf-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function rewritePlaylistWithPresignedSegments(string $body, string $manifestKey, string $diskName, \DateTimeInterface $expires): string
    {
        $dir = trim(dirname($manifestKey), '/');
        $lines = preg_split('/\r\n|\r|\n/', $body) ?: [];
        $disk = Storage::disk($diskName);
        $out = [];

        foreach ($lines as $line) {
            $trim = rtrim($line, "\r\n");
            $t = trim($trim);

            if ($t === '' || str_starts_with($t, '#')) {
                if (str_contains($t, 'URI="') && preg_match('/URI="([^"]+)"/', $t, $m)) {
                    $uri = $m[1];
                    $uriBase = basename($uri);
                    if (! str_contains($uri, '://') && $uriBase === $uri && $this->isSafeSegmentFileName($uriBase)) {
                        $segmentKey = $dir !== '' ? "{$dir}/{$uriBase}" : $uriBase;
                        if ($disk->exists($segmentKey)) {
                            $signed = $disk->temporaryUrl($segmentKey, $expires);
                            $escaped = str_replace('"', '%22', $signed);
                            $out[] = (string) preg_replace('/URI="[^"]+"/', 'URI="'.$escaped.'"', $trim, 1);

                            continue;
                        }
                    }
                }
                $out[] = $trim;

                continue;
            }

            if (str_contains($t, '://')) {
                $out[] = $trim;

                continue;
            }

            $base = basename($t);
            if (! $this->isSafeSegmentFileName($base) || ! $this->isStandaloneHlsMediaFile($base)) {
                $out[] = $trim;

                continue;
            }

            $segmentKey = $dir !== '' ? "{$dir}/{$base}" : $base;
            if (! $disk->exists($segmentKey)) {
                $out[] = $trim;

                continue;
            }

            $out[] = $disk->temporaryUrl($segmentKey, $expires);
        }

        return implode("\n", $out);
    }

    private function isSafeSegmentFileName(string $name): bool
    {
        $base = basename($name);
        if ($base === '' || $base === '.' || $base === '..' || strlen($base) > 220) {
            return false;
        }

        return preg_match('/^[a-zA-Z0-9._\-]+$/', $base) === 1;
    }

    /**
     * Righe “media” su playlist (non sotto-playlist .m3u8: richiederebbero un altro rewrite).
     */
    private function isStandaloneHlsMediaFile(string $base): bool
    {
        return (bool) preg_match('/\.(ts|m4s|aac|mp4|vtt)$/i', $base);
    }
}
