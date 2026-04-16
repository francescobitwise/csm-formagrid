<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAuditLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'staff_audit_logs';

    protected $fillable = [
        'user_id',
        'route_name',
        'http_method',
        'path',
        'ip_address',
        'user_agent',
        'response_status',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
            'response_status' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
