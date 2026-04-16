<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Admin;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Tenant;
use Illuminate\Http\RedirectResponse;

final class BillingPortalController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $error = null;
        $landlord = Tenant::query()->find(tenant('id'));
        if ($landlord === null) {
            $error = 'Organizzazione non trovata.';
        } elseif (! filled(config('cashier.secret'))) {
            $error = 'Fatturazione non configurata.';
        } elseif (! filled($landlord->stripe_id)) {
            $error = 'Non risulta ancora un account di fatturazione collegato.';
        }

        if (is_string($error)) {
            return redirect()
                ->route('tenant.admin.billing.invoices')
                ->withErrors(['billing' => $error]);
        }

        /** @var Tenant $landlord */
        return redirect()->away($landlord->billingPortalUrl(route('tenant.admin.billing.invoices')));
    }
}

