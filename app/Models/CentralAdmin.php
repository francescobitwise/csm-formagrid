<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CentralAdminFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Amministratore piattaforma (SaaS) sul DB landlord.
 */
class CentralAdmin extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    protected $connection = 'landlord';

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): CentralAdminFactory
    {
        return CentralAdminFactory::new();
    }
}
