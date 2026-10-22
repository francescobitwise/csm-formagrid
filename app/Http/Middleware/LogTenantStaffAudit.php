<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant\User;
use App\Services\StaffAuditLogger;
use App\Support\TenantImpersonation;
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
            $this->logger->logFromHttpExchange($user, $request, $response, $this->impersonationMetadata());
        } catch (\Throwable) {
            // Non bloccare la risposta se il registro fallisce.
        }
    }

    /**
     * @return array<string, string>
     */
    private function impersonationMetadata(): array
    {
        if (! TenantImpersonation::isActive()) {
            return [];
        }

        $impersonator = User::query()->whereKey(TenantImpersonation::impersonatorId())->first();
        if (! $impersonator instanceof User) {
            return [];
        }

        return [
            'autologin_da' => (string) $impersonator->email,
        ];
    }

    private function shouldLog(Request $request): bool
    {
        $method = $request->method();

        if ($request->routeIs(
            'tenant.admin.learners.impersonate',
            'tenant.admin.companies.impersonate',
        )) {
            return false;
        }

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return true;
        }

        if ($method === 'GET' && $request->routeIs(
            'tenant.admin.courses.learners.pdf',
            'tenant.admin.courses.learners.report.pdf',
            'tenant.admin.courses.companies-report.csv',
            'tenant.admin.dashboard.export',
        )) {
            return true;
        }

        return false;
    }
}
