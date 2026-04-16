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
     * Single-client fork: quote/piani rimossi (illimitato).
     *
     * @return array<string, mixed>
     */
    public function effectiveLimits(): array
    {
        return [
            'learners_max' => -1,
            'courses' => -1,
            'storage_gb' => -1,
            'custom_domain' => false,
        ];
    }

    public function maxLearners(): int
    {
        return -1;
    }

    public function currentLearnerCount(): int
    {
        return User::query()->where('role', UserRole::Learner)->count();
    }

    public function canAddLearners(int $count): bool
    {
        return true;
    }

    /**
     * Posti liberi per nuovi learner; null = illimitato.
     */
    public function remainingLearnerSlots(): ?int
    {
        return null;
    }

    public function maxCourses(): int
    {
        return -1;
    }

    public function currentCourseCount(): int
    {
        return Course::query()->count();
    }

    public function canAddCourse(): bool
    {
        return true;
    }

    public function remainingCourseSlots(): ?int
    {
        return null;
    }

    public function allowsCustomDomain(): bool
    {
        return false;
    }

    /**
     * Quota storage in GB dal piano (-1 = illimitato in config, trattato come molto alto).
     */
    public function maxStorageGb(): int
    {
        return -1;
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
