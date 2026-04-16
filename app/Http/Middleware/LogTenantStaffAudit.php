<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant\User;
use App\Services\StaffAuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class LogTenantStaffAudit
{
    public function __construct(
        private readonly StaffAuditLogger $logger,
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $request->routeIs('tenant.admin.*')) {
            return;
        }

        $user = $request->user();
        if (! $user instanceof User || ! $user->isStaffMember()) {
            return;
        }

        if (! $this->shouldLog($request)) {
            return;
        }

        try {
            $this->logger->logFromHttpExchange($user, $request, $response);
        } catch (\Throwable) {
            // Non bloccare la risposta se il registro fallisce.
        }
    }

    private function shouldLog(Request $request): bool
    {
        $method = $request->method();

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return true;
        }

        if ($method === 'GET' && $request->routeIs(
            'tenant.admin.billing.invoices.pdf',
            'tenant.admin.courses.learners.pdf',
            'tenant.admin.dashboard.export',
        )) {
            return true;
        }

        return false;
    }
}
