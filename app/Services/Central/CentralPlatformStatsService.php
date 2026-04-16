<?php

declare(strict_types=1);

namespace App\Services\Central;

use App\Enums\UserRole;
use App\Models\Landlord\Tenant;
use App\Models\Tenant\Course;
use App\Models\Tenant\Enrollment;
use App\Models\Tenant\Lesson;
use App\Models\Tenant\Module;
use App\Models\Tenant\User;
use App\Services\MediaStorageUsageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class CentralPlatformStatsService
{
    private const CACHE_TTL_SECONDS = 90;

    /**
     * @return array{
     *     generated_at: string,
     *     totals: array{
     *         tenants: int,
     *         courses: int,
     *         learners: int,
     *         staff: int,
     *         modules: int,
     *         lessons: int,
     *         enrollments: int,
     *         storage_bytes: int,
     *         storage_partial: bool
     *     },
     *     tenants: list<array{
     *         id: string,
     *         company_name: string|null,
     *         plan: string|null,
     *         primary_domain: string|null,
     *         created_at: string|null,
     *         courses: int,
     *         learners: int,
     *         staff: int,
     *         modules: int,
     *         lessons: int,
     *         enrollments: int,
     *         storage_bytes: int|null,
     *         storage_known: bool,
     *         error: string|null
     *     }>
     * }
     */
    public function collect(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget($this->cacheKey());
        }

        return Cache::remember($this->cacheKey(), self::CACHE_TTL_SECONDS, function () {
            return $this->compute();
        });
    }

    public function forgetCache(): void
    {
        Cache::forget($this->cacheKey());
    }

    private function cacheKey(): string
    {
        return 'central.platform.stats.v2';
    }

    private function compute(): array
    {
        $tenantModels = Tenant::query()->with('domains')->orderBy('created_at')->get();

        $totals = [
            'tenants' => $tenantModels->count(),
            'courses' => 0,
            'learners' => 0,
            'staff' => 0,
            'modules' => 0,
            'lessons' => 0,
            'enrollments' => 0,
            'storage_bytes' => 0,
            'storage_partial' => false,
        ];

        $rows = [];

        foreach ($tenantModels as $tenant) {
            $row = [
                'id' => $tenant->id,
                'company_name' => $tenant->company_name,
                'plan' => $tenant->plan,
                'primary_domain' => $tenant->domains->first()?->domain,
                'created_at' => $tenant->created_at?->toIso8601String(),
                'courses' => 0,
                'learners' => 0,
                'staff' => 0,
                'modules' => 0,
                'lessons' => 0,
                'enrollments' => 0,
                'storage_bytes' => null,
                'storage_known' => false,
                'error' => null,
            ];

            try {
                $tenant->run(function () use (&$row, &$totals): void {
                    $row['courses'] = Course::query()->count();
                    $row['learners'] = User::query()->where('role', UserRole::Learner)->count();
                    $row['staff'] = User::query()->whereIn('role', [UserRole::Admin, UserRole::Instructor])->count();
                    $row['modules'] = Module::query()->count();
                    $row['lessons'] = Lesson::query()->count();
                    $row['enrollments'] = Enrollment::query()->count();

                    [$bytes, $known] = app(MediaStorageUsageService::class)->measureCurrentTenantMediaBytes();
                    $row['storage_bytes'] = $bytes;
                    $row['storage_known'] = $known;

                    $totals['courses'] += $row['courses'];
                    $totals['learners'] += $row['learners'];
                    $totals['staff'] += $row['staff'];
                    $totals['modules'] += $row['modules'];
                    $totals['lessons'] += $row['lessons'];
                    $totals['enrollments'] += $row['enrollments'];
                    if ($known && $bytes !== null) {
                        $totals['storage_bytes'] += $bytes;
                    } else {
                        $totals['storage_partial'] = true;
                    }
                });
            } catch (\Throwable $e) {
                Log::warning('central.stats.tenant_failed', [
                    'tenant_id' => $tenant->id,
                    'message' => $e->getMessage(),
                ]);
                $row['error'] = $e->getMessage();
                $totals['storage_partial'] = true;
            }

            $rows[] = $row;
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'totals' => $totals,
            'tenants' => $rows,
        ];
    }
}
