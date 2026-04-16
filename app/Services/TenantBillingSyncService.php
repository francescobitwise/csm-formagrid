<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Landlord\Tenant;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Subscription as CashierSubscription;

/**
 * Sincronizza `tenants.data.plan` e `limits` dal prezzo Stripe attivo (Cashier subscription).
 */
final class TenantBillingSyncService
{
    public function __construct(
        private readonly TenantProvisioningService $provisioning,
    ) {}

    public function syncFromSubscription(CashierSubscription $subscription): void
    {
        $owner = $subscription->owner;
        if (! $owner instanceof Tenant) {
            return;
        }

        if (data_get($owner->getAttribute('billing'), 'manual_exempt')) {
            return;
        }

        $priceId = $subscription->stripe_price
            ?? $subscription->items()->first()?->stripe_price;

        if (! is_string($priceId) || $priceId === '') {
            return;
        }

        $plan = $this->planKeyForStripePriceId($priceId);
        if ($plan === null) {
            Log::warning('billing.unknown_stripe_price', [
                'tenant_id' => $owner->getKey(),
                'stripe_price' => $priceId,
            ]);

            return;
        }

        $this->applyPlanToTenant($owner, $plan, $subscription);
    }

    /**
     * Piano manuale (senza Stripe) o override operativo.
     */
    public function applyPlanKey(Tenant $tenant, string $plan, bool $manual = false): void
    {
        if (! array_key_exists($plan, config('tenant_plans.plans', []))) {
            throw new \InvalidArgumentException('Piano non valido: '.$plan);
        }

        $tenant->plan = $plan;
        $tenant->limits = $this->provisioning->planLimits($plan);
        $existingBilling = $tenant->getAttribute('billing');
        if (! is_array($existingBilling)) {
            $existingBilling = [];
        }
        $tenant->billing = array_merge($existingBilling, [
            'manual' => $manual,
            'manual_exempt' => $manual,
            'synced_at' => now()->toIso8601String(),
        ]);

        $tenant->save();
    }

    private function applyPlanToTenant(Tenant $tenant, string $plan, CashierSubscription $subscription): void
    {
        $tenant->plan = $plan;
        $tenant->limits = $this->provisioning->planLimits($plan);
        $existingBilling = $tenant->getAttribute('billing');
        if (! is_array($existingBilling)) {
            $existingBilling = [];
        }
        $tenant->billing = array_merge($existingBilling, [
            'manual' => false,
            'manual_exempt' => false,
            'stripe_status' => $subscription->stripe_status,
            'stripe_subscription_id' => $subscription->stripe_id,
            'synced_at' => now()->toIso8601String(),
        ]);

        $tenant->save();
    }

    private function planKeyForStripePriceId(string $priceId): ?string
    {
        $map = config('billing.stripe_price_to_plan', []);

        return $map[$priceId] ?? null;
    }
}
