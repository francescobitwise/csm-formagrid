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
use App\Support\HlsPlaylist;
use App\Support\MediaStorage;
use Illuminate\Contracts\Filesystem\Filesystem;
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
        private readonly HlsPlaylist $hls,
    ) {}

    public function __invoke(Request $request, Course $course, Lesson $lesson): SymfonyResponse
    {
        $resolved = $this->authorizeHls($request->user(), $course, $lesson);
        abort_unless($resolved !== null, 404);

        [, $manifestKey, $disk] = $resolved;

        return $this->hls->manifestResponse(
            $disk,
            $manifestKey,
            fn (string $dir, string $ref): ?string => $this->absoluteMediaUrl($this->segmentDeliveryMode(), $course, $lesson, $dir, $ref),
        );
    }

    public function file(Request $request, Course $course, Lesson $lesson, string $file): StreamedResponse|SymfonyResponse
    {
        $resolved = $this->authorizeHls($request->user(), $course, $lesson);
        abort_unless($resolved !== null, 404);

        [, $manifestKey, $disk] = $resolved;

        return $this->hls->fileResponse(
            $disk,
            $manifestKey,
            $file,
            fn (string $dir, string $ref): ?string => $this->absoluteMediaUrl($this->segmentDeliveryMode(), $course, $lesson, $dir, $ref),
        );
    }

    /**
     * @return array{0: VideoLesson, 1: string, 2: Filesystem}|null
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

    private function absoluteMediaUrl(string $mode, Course $course, Lesson $lesson, string $dir, string $ref): ?string
    {
        $key = $this->hls->resolveRelativeObjectKey($dir, $ref);
        if ($key === null || ! $this->hls->isRewritablePlaylistRef($ref)) {
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
}
