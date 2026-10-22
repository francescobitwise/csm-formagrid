<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Admin;

use App\Enums\LessonType;
use App\Enums\ProcessingStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Lesson;
use App\Models\Tenant\Module;
use App\Models\Tenant\VideoLesson;
use App\Support\HlsPlaylist;
use App\Support\MediaStorage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Anteprima admin: il player resta sul sito e i segmenti escono dal bucket tramite l'app.
 */
final class VideoPreviewController extends Controller
{
    public function __construct(
        private readonly HlsPlaylist $hls,
    ) {}

    public function show(Module $module, Lesson $lesson): SymfonyResponse
    {
        $video = $this->readyVideo($module, $lesson);

        return response()->view('tenant.admin.modules.video-preview', [
            'module' => $module,
            'lesson' => $lesson,
            'video' => $video,
            'manifestUrl' => route('tenant.admin.modules.lessons.video.preview.manifest', [$module, $lesson]),
        ]);
    }

    public function manifest(Module $module, Lesson $lesson): SymfonyResponse
    {
        [, $manifestKey, $disk] = $this->resolve($module, $lesson);

        return $this->hls->manifestResponse(
            $disk,
            $manifestKey,
            fn (string $dir, string $ref): ?string => $this->proxyUrl($module, $lesson, $dir, $ref),
        );
    }

    public function file(Module $module, Lesson $lesson, string $file): StreamedResponse|SymfonyResponse
    {
        [, $manifestKey, $disk] = $this->resolve($module, $lesson);

        return $this->hls->fileResponse(
            $disk,
            $manifestKey,
            $file,
            fn (string $dir, string $ref): ?string => $this->proxyUrl($module, $lesson, $dir, $ref),
        );
    }

    private function readyVideo(Module $module, Lesson $lesson): VideoLesson
    {
        [$video] = $this->resolve($module, $lesson);

        return $video;
    }

    /**
     * @return array{0: VideoLesson, 1: string, 2: Filesystem}
     */
    private function resolve(Module $module, Lesson $lesson): array
    {
        abort_unless($lesson->module_id === $module->id, 404);
        abort_unless($lesson->type === LessonType::Video, 404);

        $lesson->loadMissing('videoLesson');
        $video = $lesson->videoLesson;
        abort_unless($video instanceof VideoLesson, 404);
        abort_unless($video->status === ProcessingStatus::Ready, 404);

        $manifestKey = $video->hls_manifest;
        abort_unless(is_string($manifestKey) && $manifestKey !== '', 404);

        $disk = Storage::disk(MediaStorage::disk());
        abort_unless($disk->exists($manifestKey), 404);

        return [$video, $manifestKey, $disk];
    }

    private function proxyUrl(Module $module, Lesson $lesson, string $dir, string $ref): ?string
    {
        $key = $this->hls->resolveRelativeObjectKey($dir, $ref);
        if ($key === null || ! $this->hls->isRewritablePlaylistRef($ref)) {
            return null;
        }

        $relative = $dir !== '' && str_starts_with($key, $dir.'/')
            ? substr($key, strlen($dir) + 1)
            : basename($key);

        $refNorm = ltrim(str_replace('\\', '/', $ref), '/');
        if (str_contains($refNorm, '/')) {
            $relative = $refNorm;
        }

        return route('tenant.admin.modules.lessons.video.preview.file', [
            'module' => $module,
            'lesson' => $lesson,
            'file' => $relative,
        ], absolute: true);
    }
}
