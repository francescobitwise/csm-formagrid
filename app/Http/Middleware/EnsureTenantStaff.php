<?php

namespace App\Http\Middleware;

use App\Support\TenantPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo utenti staff tenant (admin o istruttore), non learner.
 */
class EnsureTenantStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! TenantPermissions::staff($user)) {
            abort(403, 'Accesso riservato allo staff del tenant.');
        }

        return $next($request);
    }
}
