<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Landlord\Tenant;
use Illuminate\Support\Str;

class TenantProvisioningService
{
    /**
     * @return list<string>
     */
    public static function planIds(): array
    {
        return array_keys(config('tenant_plans.plans', []));
    }

    /**
     * Piani selezionabili in registrazione con checkout Stripe.
     *
     * @return list<string>
     */
    public static function checkoutPlanIds(): array
    {
        $out = [];
        foreach ((array) config('tenant_plans.plans', []) as $key => $meta) {
            if (! is_string($key) || $key === '') {
                continue;
            }
            $contactOnly = (bool) ($meta['contact_only'] ?? false);
            if ($contactOnly) {
                continue;
            }
            $out[] = $key;
        }

        return $out;
    }

    public function provision(array $data): Tenant
    {
        $tenantId = Str::slug((string) ($data['tenant_id'] ?? $data['company_name'] ?? 'tenant'));

        $plan = (string) ($data['plan'] ?? config('tenant_plans.default', 'pro'));
        if (! array_key_exists($plan, config('tenant_plans.plans', []))) {
            $plan = (string) config('tenant_plans.default', 'pro');
        }

        $payload = [
            'plan' => $plan,
            'company_name' => $data['company_name'] ?? $tenantId,
            'limits' => $this->planLimits($plan),
        ];

        $billingEmail = $data['billing_email'] ?? null;
        if (is_string($billingEmail) && $billingEmail !== '') {
            $payload['billing_email'] = $billingEmail;
        }

        /*
         * VirtualColumn (Stancl): non passare la chiave `data` in create: id e data sono "custom columns",
         * quindi encodeAttributes() esclude tutto e salva data JSON vuoto. Merge dei campi virtuali a root.
         */
        /** @var Tenant $tenant */
        $tenant = Tenant::create(array_merge(
            ['id' => $tenantId],
            $payload,
        ));

        $centralDomain = (string) config('app.central_domain', env('CENTRAL_DOMAIN', 'localhost'));
        $tenant->domains()->create([
            'domain' => "{$tenantId}.{$centralDomain}",
        ]);

        return $this->ensureLandlordTenantPayload(
            $tenant->fresh(['domains']),
            $plan,
            (string) ($data['company_name'] ?? $tenantId),
            $billingEmail,
        );
    }

    /**
     * Dopo CreateDatabase/makeCredentials il blob `data` deve ancora contenere plan e limiti.
     * Se mancano (bug VirtualColumn + timestamp o tenant vecchi), li reintegra e salva.
     *
     * @param  string|null  $billingEmail  email fatturazione se nota
     */
    public function ensureLandlordTenantPayload(Tenant $tenant, string $plan, string $companyName, ?string $billingEmail = null): Tenant
    {
        $planKey = (is_string($tenant->plan) && $tenant->plan !== '') ? $tenant->plan : $plan;
        if (! array_key_exists($planKey, config('tenant_plans.plans', []))) {
            $planKey = (string) config('tenant_plans.default', 'pro');
        }

        $dirty = false;

        if (! is_string($tenant->plan) || $tenant->plan === '') {
            $tenant->plan = $planKey;
            $dirty = true;
        }

        if (! is_string($tenant->company_name) || $tenant->company_name === '') {
            $tenant->company_name = $companyName;
            $dirty = true;
        }

        $currentLimits = $tenant->limits;
        if ($dirty || ! is_array($currentLimits) || $currentLimits === []) {
            $tenant->limits = $this->planLimits($planKey);
            $dirty = true;
        }

        if (is_string($billingEmail) && $billingEmail !== '' && (! is_string($tenant->billing_email) || $tenant->billing_email === '')) {
            $tenant->billing_email = $billingEmail;
            $dirty = true;
        }

        if ($dirty) {
            $tenant->save();
        }

        return $tenant->fresh(['domains']);
    }

    /**
     * Limiti persistiti sul tenant (usati da TenantQuotaService con merge config).
     *
     * @return array{courses: int, learners_max: int, storage_gb: int, custom_domain: bool}
     */
    public function planLimits(string $plan): array
    {
        $plans = config('tenant_plans.plans', []);
        $defaultId = (string) config('tenant_plans.default', 'pro');
        $def = $plans[$defaultId] ?? [];

        if (! isset($plans[$plan])) {
            return $this->normalizeLimitRow($def);
        }

        return $this->normalizeLimitRow($plans[$plan]);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{courses: int, learners_max: int, storage_gb: int, custom_domain: bool}
     */
    private function normalizeLimitRow(array $row): array
    {
        return [
            'courses' => (int) ($row['courses'] ?? 100),
            'learners_max' => (int) ($row['learners_max'] ?? 500),
            'storage_gb' => (int) ($row['storage_gb'] ?? 50),
            'custom_domain' => (bool) ($row['custom_domain'] ?? true),
        ];
    }
}
