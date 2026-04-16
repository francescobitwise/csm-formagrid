<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\ScormTrackingStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScormTracking extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'data_model' => 'array',
        'status' => ScormTrackingStatus::class,
        'last_sync_at' => 'datetime',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(ScormPackage::class, 'scorm_package_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
