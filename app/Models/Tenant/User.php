<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\UserRole;
use App\Models\Tenant\Company;
use App\Notifications\TenantResetPasswordNotification;
use Database\Factories\Tenant\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'company_id', 'credentials_sent_at', 'must_change_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'credentials_sent_at' => 'datetime',
            'must_change_password' => 'boolean',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /** Valore ruolo normalizzato (enum o stringa grezza in DB). */
    public function tenantRoleValue(): string
    {
        $r = $this->role;
        if ($r instanceof UserRole) {
            return $r->value;
        }

        return strtolower(trim((string) ($this->getRawOriginal('role') ?? '')));
    }

    public function isLearner(): bool
    {
        return $this->tenantRoleValue() === UserRole::Learner->value;
    }

    /** Admin o istruttore — mai learner. */
    public function isStaffMember(): bool
    {
        return in_array($this->tenantRoleValue(), [UserRole::Admin->value, UserRole::Instructor->value], true);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new TenantResetPasswordNotification($token));
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
