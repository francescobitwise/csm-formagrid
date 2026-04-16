<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\LessonType;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Lesson;
use App\Models\Tenant\Module;
use App\Services\TenantQuotaService;
use App\Services\VideoLessonSourceService;
use App\Support\MediaStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VideoDirectUploadController extends Controller
{
    private const CACHE_PREFIX = 'video_direct_upload:';

    public function presign(Request $request, TenantQuotaService $quota): JsonResponse
    {
        $data = $request->validate([
            'module_id' => ['required', 'uuid', 'exists:modules,id'],
            'lesson_id' => ['required', 'uuid', 'exists:lessons,id'],
            'content_type' => ['required', 'string', Rule::in([
                'video/mp4',
                'application/vnd.apple.mpegurl',
                'application/x-mpegURL',
            ])],
            'expected_size' => ['nullable', 'integer', 'min:1', 'max:53687091200'],
        ]);

        if (isset($data['expected_size']) && ! $quota->canAcceptUploadBytes((int) $data['expected_size'])) {
            return response()->json([
                'message' => 'Spazio storage insufficiente per il piano (stima dimensione file).',
                'code' => 'storage_quota',
            ], 422);
        }

        $diskName = MediaStorage::disk();
        if (config("filesystems.disks.{$diskName}.driver") !== 's3') {
            return response()->json([
                'message' => 'Upload diretto è disponibile solo con disco object storage S3-compatibile (MEDIA_DISK=s3).',
                'code' => 's3_required',
            ], 422);
        }

        $module = Module::query()->findOrFail($data['module_id']);
        $lesson = Lesson::query()->findOrFail($data['lesson_id']);

        if ($lesson->module_id !== $module->id) {
            abort(404);
        }

        if ($lesson->type !== LessonType::Video) {
            throw ValidationException::withMessages([
                'lesson_id' => ['La lezione deve essere di tipo video.'],
            ]);
        }

        $ext = $data['content_type'] === 'video/mp4' ? 'mp4' : 'm3u8';
        $tenantId = (string) (tenant('id') ?? 'central');
        $objectKey = "tenants/{$tenantId}/video-source/".uniqid('video_', true).'.'.$ext;

        $disk = Storage::disk($diskName);
        $signed = $disk->temporaryUploadUrl($objectKey, now()->addMinutes(20), [
            'ContentType' => $data['content_type'],
        ]);

        $token = Str::random(48);
        Cache::put(self::CACHE_PREFIX.$token, [
            'user_id' => $request->user()->id,
            'object_key' => $objectKey,
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
        ], now()->addMinutes(30));

        $headers = [];
        foreach ($signed['headers'] as $name => $values) {
            $headers[$name] = is_array($values) ? implode(',', $values) : (string) $values;
        }

        return response()->json([
            'upload_url' => $signed['url'],
            'headers' => $headers,
            'object_key' => $objectKey,
            'upload_token' => $token,
            'expires_in' => 1200,
        ]);
    }

    public function finalize(Request $request, VideoLessonSourceService $videoLessonSource): JsonResponse
    {
        $data = $request->validate([
            'upload_token' => ['required', 'string', 'size:48'],
        ]);

        $payload = Cache::get(self::CACHE_PREFIX.$data['upload_token']);
        if (! $payload || (int) $payload['user_id'] !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'Sessione di upload scaduta o non valida. Riprova dall’inizio.',
            ], 422);
        }

        $lesson = Lesson::query()->findOrFail($payload['lesson_id']);
        if ($lesson->module_id !== $payload['module_id']) {
            abort(403);
        }

        $diskName = MediaStorage::disk();
        if (! Storage::disk($diskName)->exists($payload['object_key'])) {
            return response()->json([
                'message' => 'Il file non risulta ancora nello storage. Attendi il completamento dell’upload diretto e riprova “Registra upload”.',
            ], 422);
        }

        $tenantId = (string) (tenant('id') ?? 'central');

        try {
            $videoLessonSource->attachStoredSource($lesson, $payload['object_key'], $tenantId);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        Cache::forget(self::CACHE_PREFIX.$data['upload_token']);

        return response()->json([
            'ok' => true,
            'message' => 'File ricevuto: conversione in coda.',
        ]);
    }
}
