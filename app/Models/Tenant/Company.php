<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Company extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'legal_name',
        'vat',
        'email',
        'phone',
        'contact_name',
        'address_line1',
        'address_line2',
        'postal_code',
        'city',
        'province',
        'country',
        'notes',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

