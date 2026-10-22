<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Il responsabile azienda usa solo i report admin, non il catalogo allievo.
 */
final class RedirectCompanyReportViewer
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isCompanyManager()) {
            return $next($request);
        }

        if ($request->routeIs(
            'tenant.home',
            'tenant.dashboard',
            'tenant.courses.*',
            'tenant.lessons.*',
            'tenant.learner.*',
            'tenant.scorm.asset',
        )) {
            return redirect()->route('tenant.admin.courses.index');
        }

        return $next($request);
    }
}
