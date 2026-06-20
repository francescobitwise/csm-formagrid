<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\PlatformSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RecordPlatformSession
{
    public function __construct(private readonly PlatformSessionService $platformSessionService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if ($user && method_exists($user, 'getKey')) {
            // Record on successful responses only; avoid noise from errors/redirect loops.
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 500) {
                $this->platformSessionService->recordHit(
                    userId: (string) $user->getKey(),
                    occurredAt: now(),
                    ipAddress: $request->ip(),
                    userAgent: $request->userAgent(),
                );
            }
        }

        return $response;
    }
}

