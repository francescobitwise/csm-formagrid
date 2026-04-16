<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Tenant\Course;
use App\Models\Tenant\User;

/**
 * Quote piano tenant (VirtualColumn: `plan` / `limits` sono attributi su `tenant()`, non `tenant()->data`).
 */
final class TenantQuotaService
{
    public function __construct(
        private readonly MediaStorageUsageService $mediaStorageUsage,
    ) {}

    /**
     * Limiti effettivi (config piano + override salvato su landlord).
     *
     * @return array<string, mixed>
     */
    public function effectiveLimits(): array
    {
        $t = tenant();
        $plan = (string) ($t?->getAttribute('plan') ?? config('tenant_plans.default', 'pro'));
        $base = config("tenant_plans.plans.{$plan}", []);
        $stored = $t?->getAttribute('limits');
        if (! is_array($stored)) {
            $stored = [];
        }

        return array_merge(is_array($base) ? $base : [], is_array($stored) ? $stored : []);
    }

    public function maxLearners(): int
    {
        return (int) ($this->effectiveLimits()['learners_max'] ?? -1);
    }

    public function currentLearnerCount(): int
    {
        return User::query()->where('role', UserRole::Learner)->count();
    }

    public function canAddLearners(int $count): bool
    {
        if ($count <= 0) {
            return true;
        }
        $max = $this->maxLearners();
        if ($max < 0) {
            return true;
        }

        return $this->currentLearnerCount() + $count <= $max;
    }

    /**
     * Posti liberi per nuovi learner; null = illimitato.
     */
    public function remainingLearnerSlots(): ?int
    {
        $max = $this->maxLearners();
        if ($max < 0) {
            return null;
        }

        return max(0, $max - $this->currentLearnerCount());
    }

    public function maxCourses(): int
    {
        return (int) ($this->effectiveLimits()['courses'] ?? -1);
    }

    public function currentCourseCount(): int
    {
        return Course::query()->count();
    }

    public function canAddCourse(): bool
    {
        $max = $this->maxCourses();
        if ($max < 0) {
            return true;
        }

        return $this->currentCourseCount() < $max;
    }

    public function remainingCourseSlots(): ?int
    {
        $max = $this->maxCourses();
        if ($max < 0) {
            return null;
        }

        return max(0, $max - $this->currentCourseCount());
    }

    public function allowsCustomDomain(): bool
    {
        return (bool) ($this->effectiveLimits()['custom_domain'] ?? false);
    }

    /**
     * Quota storage in GB dal piano (-1 = illimitato in config, trattato come molto alto).
     */
    public function maxStorageGb(): int
    {
        return (int) ($this->effectiveLimits()['storage_gb'] ?? 0);
    }

    /**
     * Limite in byte; -1 = illimitato.
     */
    public function maxStorageBytes(): int
    {
        $gb = $this->maxStorageGb();
        if ($gb < 0) {
            return -1;
        }

        return $gb * 1024 * 1024 * 1024;
    }

    /**
     * Uso stimato media; null se non calcolabile (troppi file o disco non supportato).
     */
    public function currentMediaUsageBytes(): ?int
    {
        [$bytes, $known] = $this->mediaStorageUsage->measureCurrentTenantMediaBytes();

        return $known ? $bytes : null;
    }

    /**
     * Se non riusciamo a misurare, consentiamo l’upload (fail-open).
     */
    public function canAcceptUploadBytes(int $additionalBytes): bool
    {
        if ($additionalBytes < 0) {
            return false;
        }
        $max = $this->maxStorageBytes();
        if ($max < 0) {
            return true;
        }
        $current = $this->currentMediaUsageBytes();
        if ($current === null) {
            return true;
        }

        return $current + $additionalBytes <= $max;
    }

    public function remainingStorageBytes(): ?int
    {
        $max = $this->maxStorageBytes();
        if ($max < 0) {
            return null;
        }
        $current = $this->currentMediaUsageBytes();
        if ($current === null) {
            return null;
        }

        return max(0, $max - $current);
    }
}
