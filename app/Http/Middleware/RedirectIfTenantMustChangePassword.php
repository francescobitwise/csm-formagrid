<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocca l’uso della piattaforma finché l’utente tenant non ha scelto una password propria
 * (obbligo dopo credenziali provvisorie / generate).
 */
final class RedirectIfTenantMustChangePassword
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs(
            'tenant.password.required',
            'tenant.password.required.update',
            'tenant.logout',
        )) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Devi aggiornare la password prima di continuare.',
                'must_change_password' => true,
            ], 403);
        }

        return redirect()->route('tenant.password.required');
    }
}
