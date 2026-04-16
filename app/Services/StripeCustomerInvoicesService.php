<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Landlord\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Laravel\Cashier\Invoice as CashierInvoice;

/**
 * Elenco fatture Stripe (Cashier) per un tenant Billable.
 */
final class StripeCustomerInvoicesService
{
    /**
     * @return Collection<int, array{id: string, number: ?string, date: CarbonInterface, total: string, status: string, paid: bool}>
     */
    public function listForTenant(Tenant $tenant, int $limit = 48): Collection
    {
        if (! filled($tenant->stripe_id)) {
            return collect();
        }

        $tz = config('app.timezone') ?: 'UTC';

        return $tenant->invoicesIncludingPending(['limit' => $limit])
            ->map(function (CashierInvoice $inv) use ($tz): array {
                $stripe = $inv->asStripeInvoice();

                return [
                    'id' => $inv->id,
                    'number' => $stripe->number !== null && $stripe->number !== '' ? (string) $stripe->number : null,
                    'date' => $inv->date($tz),
                    'total' => $inv->total(),
                    'status' => (string) $stripe->status,
                    'paid' => $inv->isPaid(),
                ];
            });
    }
}
