<?php

namespace App\Http\Middleware;

use App\Enums\TenantPermission;
use App\Support\TenantPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        $enum = TenantPermission::tryFrom($permission);
        if ($enum === null) {
            abort(500, 'Permesso non valido.');
        }

        if (! TenantPermissions::allows($user, $enum)) {
            abort(403, 'Non hai i permessi per questa azione.');
        }

        return $next($request);
    }
}
