<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Login amministratore tenant tramite URL firmato generato dalla central (solo SaaS admin).
 */
final class SaasTenantAdminLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $userId = (string) $request->query('user', '');
        abort_unless($userId !== '', 404);

        $user = User::query()
            ->whereKey($userId)
            ->where('role', UserRole::Admin)
            ->first();

        abort_if($user === null, 403);

        Auth::login($user, remember: false);
        $request->session()->regenerate();

        if ($user->must_change_password) {
            return redirect()->route('tenant.password.required');
        }

        return redirect()->intended(route('tenant.admin.dashboard'));
    }
}
