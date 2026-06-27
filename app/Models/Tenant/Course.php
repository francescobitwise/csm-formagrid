<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\CourseStatus;
use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Course extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $casts = [
        'settings' => 'array',
        'status' => CourseStatus::class,
        'starts_at' => 'datetime',
        'total_hours' => 'decimal:2',
        'auto_enroll' => 'boolean',
    ];

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'course_module')
            ->withPivot(['position', 'required'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(
            Lesson::class,
            CourseModule::class,
            'course_id',
            'module_id',
            'id',
            'module_id'
        );
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function learners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot(['status', 'progress_pct', 'enrolled_at', 'completed_at'])
            ->withTimestamps();
    }

    public function assignedCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'course_company_assignments')
            ->withTimestamps();
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_user_assignments')
            ->withTimestamps();
    }

    public function thumbnailPublicUrl(): ?string
    {
        $path = $this->thumbnail;
        if (! is_string($path) || $path === '') {
            return null;
        }

        return MediaStorage::url($path);
    }

    public function isVisibleToUser(User $user): bool
    {
        // Staff can always see/manage catalog
        if ($user->isStaffMember()) {
            return true;
        }

        // Direct assignment
        if ($this->assignedUsers()->whereKey($user->id)->exists()) {
            return true;
        }

        // Company assignment
        if ($user->company_id !== null && $this->assignedCompanies()->whereKey($user->company_id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Corsi pubblicati visibili nel catalogo learner (assegnazioni utente/azienda; staff vede tutto).
     */
    public function scopePublishedForLearner(Builder $query, User $user): Builder
    {
        return $query
            ->where('status', CourseStatus::Published)
            ->where(function (Builder $inner): void {
                $inner->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->when(! $user->isStaffMember(), function (Builder $inner) use ($user): void {
                $userId = $user->id;
                $companyId = $user->company_id;

                $inner->where(function (Builder $assigned) use ($userId, $companyId): void {
                    $assigned->whereExists(function ($sub) use ($userId) {
                        $sub->selectRaw('1')
                            ->from('course_user_assignments')
                            ->whereColumn('course_user_assignments.course_id', 'courses.id')
                            ->where('course_user_assignments.user_id', $userId);
                    });

                    if ($companyId !== null) {
                        $assigned->orWhereExists(function ($sub) use ($companyId) {
                            $sub->selectRaw('1')
                                ->from('course_company_assignments')
                                ->whereColumn('course_company_assignments.course_id', 'courses.id')
                                ->where('course_company_assignments.company_id', $companyId);
                        });
                    }
                });
            });
    }

    public function scopeSearchLearnerCatalog(Builder $query, ?string $term): Builder
    {
        $q = trim((string) $term);
        if ($q === '') {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';

        return $query->where(function (Builder $inner) use ($like): void {
            $inner->where('title', 'like', $like)
                ->orWhere('description', 'like', $like);
        });
    }
}
