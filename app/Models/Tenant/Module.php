<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_module')
            ->withPivot(['position', 'required'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }
}
