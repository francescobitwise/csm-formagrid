<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Landlord\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Richiede abbonamento Cashier attivo quando BILLING_ENFORCE_SUBSCRIPTION=true.
 * Esclude rotte sensibili (login, profilo) per evitare loop.
 */
final class EnsureTenantSubscriptionActive
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('billing.enforce_subscription', false)) {
            return $next($request);
        }

        if (! filled(config('cashier.secret'))) {
            return $next($request);
        }

        $t = tenant();
        if (! $t instanceof Tenant) {
            return $next($request);
        }

        if (data_get($t->getAttribute('billing'), 'manual_exempt')) {
            return $next($request);
        }

        $name = $request->route()?->getName();
        if (is_string($name) && in_array($name, config('billing.middleware_excluded_route_names', []), true)) {
            return $next($request);
        }

        if (! filled($t->stripe_id)) {
            return $next($request);
        }

        if ($t->subscribed('default')) {
            return $next($request);
        }

        return redirect()
            ->route('tenant.admin.dashboard')
            ->withErrors([
                'billing' => 'L’abbonamento non è attivo. Completa il pagamento dalla piattaforma centrale (Portale Stripe) o contatta il supporto.',
            ]);
    }
}
