<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Landlord\Tenant;
use App\Services\TenantFirstAdminProvisioningService;
use App\Support\StripeCheckoutSession;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Dopo checkout Stripe, crea il primo admin se la pagina success non viene raggiunta (webhook).
 */
final class EnsureTenantFirstAdminAfterStripeWebhook
{
    public function __construct(
        private readonly TenantFirstAdminProvisioningService $firstAdmin,
    ) {}

    public function handle(WebhookReceived $event): void
    {
        $payload = $event->payload;
        if (($payload['type'] ?? '') !== 'checkout.session.completed') {
            return;
        }

        $session = $payload['data']['object'] ?? [];
        if (! is_array($session)) {
            return;
        }

        if (($session['mode'] ?? '') !== 'subscription') {
            return;
        }

        $ref = $session['client_reference_id'] ?? null;
        if (! is_string($ref) || $ref === '') {
            return;
        }

        $tenant = Tenant::query()->with('domains')->find($ref);
        if ($tenant === null) {
            return;
        }

        $email = $tenant->stripeEmail();
        if ($email === null || $email === '') {
            $email = StripeCheckoutSession::customerEmail($session);
        }
        if ($email === null || $email === '') {
            return;
        }

        if ($tenant->stripeEmail() === null) {
            $tenant->billing_email = $email;
            $tenant->save();
        }

        $company = trim((string) ($tenant->stripeName() ?? ''));
        if ($company === '' || $company === (string) $tenant->getKey()) {
            $company = 'Amministratore';
        }

        $this->firstAdmin->ensureFirstAdmin($tenant, $email, $company);
    }
}
