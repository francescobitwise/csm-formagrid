<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\PrivacyRequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyContactRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'recorded_by_user_id',
        'contact_email',
        'request_type',
        'message',
        'status',
        'status_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PrivacyRequestStatus::class,
            'status_updated_at' => 'datetime',
        ];
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
