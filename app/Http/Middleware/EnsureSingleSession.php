<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garantisce un solo login attivo per utente: se la sessione non coincide
 * con current_session_id, scollega e reindirizza al login.
 */
final class EnsureSingleSession
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $sessionId = $request->session()->getId();

        if ($user->current_session_id === null) {
            $user->forceFill(['current_session_id' => $sessionId])->save();

            return $next($request);
        }

        if (hash_equals((string) $user->current_session_id, $sessionId)) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sei stato scollegato perché hai effettuato l’accesso da un altro dispositivo.',
            ], 401);
        }

        return redirect()
            ->route('tenant.login')
            ->with('session_replaced', 'Sei stato scollegato perché hai effettuato l’accesso da un altro dispositivo.');
    }
}
