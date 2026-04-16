<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Landlord\Tenant;
use App\Models\Tenant\User;
use App\Services\StripeCustomerInvoicesService;
use App\Services\TenantBillingSyncService;
use App\Services\TenantOperationalHealthService;
use App\Services\TenantProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class CentralTenantsController extends Controller
{
    public function index(): View
    {
        $tenantModels = Tenant::query()->with('domains')->orderBy('created_at')->get();

        $rows = [];

        foreach ($tenantModels as $tenant) {
            $adminEmail = null;
            $adminUserId = null;
            $error = null;
            $health = null;

            try {
                $tenant->run(function () use (&$adminEmail, &$adminUserId, &$health): void {
                    $admin = User::query()
                        ->where('role', UserRole::Admin)
                        ->orderBy('created_at')
                        ->first();

                    if ($admin !== null) {
                        $adminEmail = $admin->email;
                        $adminUserId = $admin->getKey();
                    }

                    $health = app(TenantOperationalHealthService::class)->snapshot();
                });
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }

            if ($error !== null) {
                $health = [
                    'level' => 'error',
                    'summary' => 'Tenant non raggiungibile',
                    'metrics' => [],
                ];
            }

            $rows[] = [
                'id' => $tenant->id,
                'company_name' => $tenant->company_name,
                'primary_domain' => $tenant->domains->first()?->domain,
                'plan' => $tenant->plan,
                'stripe_id' => $tenant->stripe_id,
                'admin_email' => $adminEmail,
                'admin_user_id' => $adminUserId,
                'error' => $error,
                'health' => $health,
            ];
        }

        return view('central.tenants', ['rows' => $rows]);
    }

    public function saasLogin(Request $request, Tenant $tenant): RedirectResponse
    {
        $domain = $tenant->domains->first()?->domain;
        if ($domain === null) {
            return back()->withErrors(['tenant' => 'Nessun dominio configurato per questo tenant.']);
        }

        $adminUserId = null;

        try {
            $tenant->run(function () use (&$adminUserId): void {
                $admin = User::query()
                    ->where('role', UserRole::Admin)
                    ->orderBy('created_at')
                    ->first();
                $adminUserId = $admin?->getKey();
            });
        } catch (\Throwable $e) {
            return back()->withErrors([
                'tenant' => 'Impossibile accedere al database tenant: '.$e->getMessage(),
            ]);
        }

        if ($adminUserId === null) {
            return back()->withErrors(['tenant' => 'Nessun amministratore trovato nel tenant.']);
        }

        $scheme = $request->getScheme();
        $root = $scheme.'://'.$domain;

        URL::forceRootUrl($root);

        try {
            $url = URL::temporarySignedRoute(
                'tenant.saas.admin-login',
                now()->addMinutes(5),
                ['user' => $adminUserId],
                absolute: true
            );
        } finally {
            URL::forceRootUrl(null);
        }

        return redirect()->away($url);
    }

    public function updatePlan(Request $request, Tenant $tenant, TenantBillingSyncService $billing): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', Rule::in(TenantProvisioningService::planIds())],
        ]);

        $billing->applyPlanKey($tenant, $data['plan'], manual: true);

        return back()->with('toast', 'Piano tenant aggiornato (override manuale).');
    }

    public function billingPortal(Tenant $tenant): RedirectResponse
    {
        if (! filled($tenant->stripe_id)) {
            return back()->withErrors([
                'tenant' => 'Questo tenant non ha ancora un cliente Stripe. Completa prima un checkout o collega un abbonamento.',
            ]);
        }

        return redirect()->away(
            $tenant->billingPortalUrl(route('central.tenants.index'))
        );
    }

    public function invoices(Tenant $tenant, StripeCustomerInvoicesService $invoices): View
    {
        $rows = $invoices->listForTenant($tenant);

        return view('central.tenant-invoices', [
            'tenant' => $tenant,
            'hasStripeCustomer' => filled($tenant->stripe_id),
            'rows' => $rows,
        ]);
    }

    public function downloadInvoice(Tenant $tenant, string $invoice): Response|RedirectResponse
    {
        if (! preg_match('/^in_[a-zA-Z0-9]+$/', $invoice)) {
            return back()->withErrors(['tenant' => 'Identificativo fattura non valido.']);
        }

        if (! filled($tenant->stripe_id)) {
            return back()->withErrors([
                'tenant' => 'Questo tenant non ha un cliente Stripe.',
            ]);
        }

        return $tenant->downloadInvoice($invoice);
    }
}
