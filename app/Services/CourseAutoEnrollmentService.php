<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\User;
use Illuminate\Support\Collection;

final class CourseAutoEnrollmentService
{
    /**
     * Iscrive i corsisti assegnati (diretti o via azienda) se il corso ha auto_enroll attivo.
     *
     * @return int Numero di nuove iscrizioni (o riattivazioni da expired)
     */
    public function syncForCourse(Course $course): int
    {
        if (! (bool) ($course->auto_enroll ?? false)) {
            return 0;
        }

        $created = 0;

        foreach ($this->assignableLearnerIds($course) as $userId) {
            if ($this->ensureEnrollment((string) $userId, (string) $course->id)) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * @return Collection<int, string>
     */
    private function assignableLearnerIds(Course $course): Collection
    {
        $course->loadMissing(['assignedUsers:id', 'assignedCompanies:id']);

        $directIds = $course->assignedUsers->pluck('id');

        $companyIds = $course->assignedCompanies->pluck('id')->filter()->values();
        $companyUserIds = collect();

        if ($companyIds->isNotEmpty()) {
            $companyUserIds = User::query()
                ->where('role', UserRole::Learner)
                ->whereIn('company_id', $companyIds)
                ->pluck('id');
        }

        return $directIds->merge($companyUserIds)->unique()->values();
    }

    private function ensureEnrollment(string $userId, string $courseId): bool
    {
        $enrollment = Enrollment::query()->firstOrCreate(
            [
                'user_id' => $userId,
                'course_id' => $courseId,
            ],
            [
                'status' => EnrollmentStatus::Active->value,
                'progress_pct' => 0,
                'enrolled_at' => now(),
            ],
        );

        if ($enrollment->wasRecentlyCreated) {
            return true;
        }

        if ($enrollment->status === EnrollmentStatus::Expired) {
            $enrollment->update([
                'status' => EnrollmentStatus::Active->value,
                'enrolled_at' => now(),
            ]);

            return true;
        }

        return false;
    }
}
