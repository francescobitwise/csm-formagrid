<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Tenant;
use App\Services\TenantFirstAdminProvisioningService;
use App\Services\TenantProvisioningService;
use App\Support\StripeCheckoutSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Laravel\Cashier\Cashier;
use Throwable;

class RegistrationController extends Controller
{
    public function checkoutSuccess(Request $request, TenantFirstAdminProvisioningService $firstAdmin): View
    {
        $sessionId = $request->query('session_id');
        $tenantDomain = $this->prepareTenantAfterStripeCheckout(
            is_string($sessionId) ? $sessionId : null,
            $firstAdmin,
        );

        $tenantUrl = null;
        if (is_string($tenantDomain) && $tenantDomain !== '') {
            $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
            $tenantUrl = $scheme.'://'.$tenantDomain.'/';
        }

        return view('central.register-success', [
            'tenantDomain' => $tenantDomain,
            'tenantUrl' => $tenantUrl,
        ]);
    }

    public function store(
        Request $request,
        TenantProvisioningService $provisioning,
        TenantFirstAdminProvisioningService $firstAdmin,
    ): RedirectResponse {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'min:2', 'max:120'],
            'tenant_id' => ['nullable', 'string', 'regex:/^[a-z0-9][a-z0-9-]{1,58}[a-z0-9]$/'],
            'plan' => ['required', Rule::in(TenantProvisioningService::checkoutPlanIds())],
            'billing_interval' => ['required', Rule::in(['monthly', 'yearly'])],
            'billing_email' => ['required', 'email', 'max:255'],
        ]);

        $requested = (string) ($data['tenant_id'] ?: $data['company_name']);
        $tenantId = $this->sanitizeTenantId($requested);
        $tenantIdError = null;

        if (! preg_match('/^[a-z0-9][a-z0-9-]{1,58}[a-z0-9]$/', $tenantId)) {
            $tenantIdError = 'Sottodominio non valido. Usa solo lettere minuscole, numeri e trattini (3–60 caratteri).';
        } elseif (Tenant::query()->whereKey($tenantId)->exists()) {
            $suggested = $this->firstAvailableTenantId($tenantId);
            $tenantIdError = "Questo sottodominio è già in uso. Prova `{$suggested}`.";
            $data['tenant_id'] = $suggested;
        }

        if ($tenantIdError !== null) {
            return back()->withInput($data)->withErrors(['tenant_id' => $tenantIdError]);
        }

        $stripeConfigured = filled(config('cashier.secret'));
        $requireCheckout = (bool) config('billing.require_checkout_on_register', true);

        if ($requireCheckout && ! $stripeConfigured) {
            return back()
                ->withInput()
                ->withErrors([
                    'plan' => 'Il pagamento non è configurato: STRIPE',
                ]);
        }

        $tenant = $provisioning->provision([
            'tenant_id' => $tenantId,
            'company_name' => $data['company_name'],
            'plan' => $data['plan'],
            'billing_email' => $data['billing_email'],
        ]);

        $domain = $tenant->domains->first()?->domain;

        if (! $requireCheckout) {
            $firstAdmin->ensureFirstAdmin(
                $tenant,
                (string) $data['billing_email'],
                (string) $data['company_name'],
            );
            $redirect = back()->with('created_domain', $domain);
        } else {
            $priceId = (string) config('billing.stripe_prices.'.$data['plan'].'.'.$data['billing_interval'], '');
            if ($priceId === '') {
                $redirect = back()
                    ->withInput()
                    ->withErrors([
                        'plan' => 'Configura gli ID prezzo Stripe (STRIPE_PRICE_*_MONTHLY/YEARLY) in .env per questo piano e periodo.',
                    ]);
            } else {
                $trialDays = (int) (config('tenant_plans.plans.'.$data['plan'].'.trial_days', 0) ?? 0);
                $builder = $tenant->newSubscription('default', $priceId);
                if ($trialDays > 0) {
                    $builder->trialDays($trialDays);
                }

                $checkout = $builder->checkout([
                    'success_url' => route('central.register.checkout-success').'?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('central.register'),
                    'client_reference_id' => (string) $tenant->getKey(),
                ], [
                    'email' => $data['billing_email'],
                ]);

                $redirect = $checkout->redirect();
            }
        }

        return $redirect;
    }

    private function sanitizeTenantId(string $input): string
    {
        $slug = (string) Str::slug($input);

        $slug = substr($slug, 0, 60);
        $slug = trim($slug, '-');

        return $slug;
    }

    private function firstAvailableTenantId(string $base): string
    {
        $base = $this->sanitizeTenantId($base);
        if ($base === '') {
            $base = 'tenant';
        }

        $i = 2;
        $candidate = $base;

        while (Tenant::query()->whereKey($candidate)->exists()) {
            $suffix = '-'.$i;
            $maxBaseLen = 60 - strlen($suffix);
            $truncated = substr($base, 0, max(1, $maxBaseLen));
            $truncated = trim($truncated, '-');

            $candidate = $truncated.$suffix;
            $i++;
        }

        return $candidate;
    }

    private function prepareTenantAfterStripeCheckout(?string $sessionId, TenantFirstAdminProvisioningService $firstAdmin): ?string
    {
        if ($sessionId === null || $sessionId === '' || ! str_starts_with($sessionId, 'cs_') || ! filled(config('cashier.secret'))) {
            return null;
        }

        try {
            $session = Cashier::stripe()->checkout->sessions->retrieve($sessionId, [
                'expand' => ['customer'],
            ]);
            $ref = $session->client_reference_id ?? null;
            if (! is_string($ref) || $ref === '') {
                return null;
            }

            $tenant = Tenant::query()->with('domains')->find($ref);
            $domain = $tenant?->domains->first()?->domain;

            if ($tenant === null) {
                return null;
            }

            $email = strtolower(trim((string) ($tenant->stripeEmail() ?? '')));
            if ($email === '') {
                $email = (string) (StripeCheckoutSession::customerEmail($session) ?? '');
            }
            if ($email !== '' && $tenant->stripeEmail() === null) {
                $tenant->billing_email = $email;
                $tenant->save();
            }

            $company = trim((string) ($tenant->stripeName() ?? ''));
            if ($company === '' || $company === (string) $tenant->getKey()) {
                $company = 'Amministratore';
            }
            if ($email !== '') {
                $firstAdmin->ensureFirstAdmin($tenant, $email, $company);
            } else {
                Log::warning('tenant.checkout_success.no_billing_email', [
                    'tenant_id' => $tenant->getKey(),
                ]);
            }

            return is_string($domain) && $domain !== '' ? $domain : null;
        } catch (Throwable $e) {
            Log::warning('tenant.checkout_success.session_failed', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
