<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\LessonType;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\Lesson;
use App\Services\LessonSequentialAccessService;
use App\Services\WatchTimeSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class DocumentPresenceController extends Controller
{
    public function __construct(
        private readonly WatchTimeSessionService $watchTimeSessionService,
        private readonly LessonSequentialAccessService $lessonSequentialAccess,
    ) {}

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lesson_id' => ['required', 'uuid'],
            'enrollment_id' => ['required', 'uuid'],
        ]);

        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $enrollment = Enrollment::query()
            ->whereKey($data['enrollment_id'])
            ->where('user_id', $user->id)
            ->first();

        if ($enrollment === null) {
            return response()->json(['message' => 'Iscrizione non valida.'], 403);
        }

        $lesson = Lesson::query()->whereKey($data['lesson_id'])->first();
        $type = (string) ($lesson?->type?->value ?? $lesson?->type ?? '');
        if ($lesson === null || $type !== LessonType::Document->value) {
            return response()->json(['message' => 'Lezione non trovata.'], 404);
        }

        $course = Course::query()->whereKey($enrollment->course_id)->first();
        $lessonInCourse = $course !== null && $course->lessons()->whereKey($lesson->id)->exists();
        if ($course === null || ! $lessonInCourse) {
            return response()->json(['message' => 'Accesso negato a questa lezione.'], 403);
        }

        if (! $this->lessonSequentialAccess->canAccessLesson($course, $lesson, $enrollment, $user)) {
            return response()->json(['message' => 'Completa le lezioni precedenti prima di proseguire.'], 403);
        }

        $this->watchTimeSessionService->recordVisibleHit(
            enrollmentId: (string) $enrollment->id,
            userId: (string) $user->id,
            courseId: (string) $enrollment->course_id,
            lessonId: (string) $lesson->id,
            sourceType: 'document',
            occurredAt: Date::now(),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response()->json(['ok' => true]);
    }
}
