<?php

declare(strict_types=1);

namespace App\Models\Landlord;

use Laravel\Cashier\Billable;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Record tenant nel database landlord (domini, provisioning DB).
 * Non confondere con App\Models\Tenant\* (modelli Eloquent del DB tenant).
 *
 * @property string|null $stripe_id
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use Billable;
    use HasDatabase;
    use HasDomains;

    protected $casts = [
        'trial_ends_at' => 'datetime',
    ];

    public static function getCustomColumns(): array
    {
        /*
         * VirtualColumn mette in `data` tutto ciò che non è “custom”. Senza i timestamp qui,
         * created_at/updated_at finiscono nel JSON; un secondo save (es. CreateDatabase::makeCredentials)
         * può ricodificare un blob incompleto e far sparire plan/company_name/limits.
         */
        return [
            'id',
            'data',
            'stripe_id',
            'pm_type',
            'pm_last_four',
            'trial_ends_at',
            'created_at',
            'updated_at',
        ];
    }

    public function stripeName(): ?string
    {
        $name = $this->getAttribute('company_name');
        if (is_string($name) && $name !== '') {
            return $name;
        }

        if (is_array($this->data)) {
            $name = $this->data['company_name'] ?? null;
            if (is_string($name) && $name !== '') {
                return $name;
            }
        }

        return (string) $this->getKey();
    }

    public function stripeEmail(): ?string
    {
        $email = $this->getAttribute('billing_email');
        if (is_string($email) && $email !== '') {
            return $email;
        }

        if (is_array($this->data)) {
            $email = $this->data['billing_email'] ?? null;
            if (is_string($email) && $email !== '') {
                return $email;
            }
        }

        return null;
    }
}
