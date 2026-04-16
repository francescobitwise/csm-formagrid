<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Admin;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Tenant;
use App\Services\StripeCustomerInvoicesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class BillingInvoicesController extends Controller
{
    public function __construct(
        private readonly StripeCustomerInvoicesService $invoices,
    ) {}

    public function index(): View|RedirectResponse
    {
        $landlord = Tenant::query()->find(tenant('id'));
        if ($landlord === null) {
            return redirect()
                ->route('tenant.admin.dashboard')
                ->withErrors(['billing' => 'Organizzazione non trovata.']);
        }

        $rows = $this->invoices->listForTenant($landlord);

        return view('tenant.admin.billing-invoices', [
            'hasStripeCustomer' => filled($landlord->stripe_id),
            'rows' => $rows,
        ]);
    }

    public function pdf(string $invoice): Response|RedirectResponse
    {
        if (! preg_match('/^in_[a-zA-Z0-9]+$/', $invoice)) {
            return redirect()
                ->route('tenant.admin.billing.invoices')
                ->withErrors(['billing' => 'Fattura non valida.']);
        }

        $landlord = Tenant::query()->find(tenant('id'));
        if ($landlord === null || ! filled($landlord->stripe_id)) {
            return redirect()
                ->route('tenant.admin.billing.invoices')
                ->withErrors(['billing' => 'Fatturazione non disponibile.']);
        }

        return $landlord->downloadInvoice($invoice);
    }
}
