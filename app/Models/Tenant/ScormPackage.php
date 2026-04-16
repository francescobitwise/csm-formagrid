<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\ProcessingStatus;
use App\Enums\ScormVersion;
use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScormPackage extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'manifest' => 'array',
        'status' => ProcessingStatus::class,
        'version' => ScormVersion::class,
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function launchUrl(): string
    {
        if (! is_string($this->s3_path) || $this->s3_path === '') {
            return '';
        }

        return MediaStorage::url($this->s3_path);
    }
}
