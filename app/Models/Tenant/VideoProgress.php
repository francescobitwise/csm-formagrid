<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoProgress extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'completed' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    public function videoLesson(): BelongsTo
    {
        return $this->belongsTo(VideoLesson::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
