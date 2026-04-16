<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\StaffAuditLog;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class StaffAuditLogger
{
    private const SENSITIVE_KEY_FRAGMENTS = [
        'password',
        'token',
        'secret',
        'credit',
        'card',
        'cvv',
        'authorization',
    ];

    public function logFromHttpExchange(User $user, Request $request, ?Response $response): void
    {
        $metadata = $this->sanitizedRequestPayload($request);

        StaffAuditLog::query()->create([
            'user_id' => $user->getKey(),
            'route_name' => $request->route()?->getName(),
            'http_method' => $request->method(),
            'path' => mb_substr($request->path(), 0, 500),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
            'response_status' => $response !== null ? $response->getStatusCode() : null,
            'metadata' => $metadata === [] ? null : $metadata,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitizedRequestPayload(Request $request): array
    {
        $input = $request->except(['_token']);

        return $this->stripSensitiveKeys(is_array($input) ? $input : []);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function stripSensitiveKeys(array $data, int $depth = 0): array
    {
        if ($depth > 4) {
            return ['_truncated' => true];
        }

        $out = [];

        foreach ($data as $key => $value) {
            $keyLower = strtolower((string) $key);
            $redact = false;
            foreach (self::SENSITIVE_KEY_FRAGMENTS as $frag) {
                if (str_contains($keyLower, $frag)) {
                    $redact = true;
                    break;
                }
            }

            if ($redact) {
                $out[$key] = '[omesso]';

                continue;
            }

            if (is_array($value)) {
                $out[$key] = $this->stripSensitiveKeys($value, $depth + 1);
            } else {
                $out[$key] = $value;
            }
        }

        return $out;
    }
}
