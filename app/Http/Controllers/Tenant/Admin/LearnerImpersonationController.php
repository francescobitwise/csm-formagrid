<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use App\Services\StaffAuditLogger;
use App\Support\TenantImpersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class LearnerImpersonationController extends Controller
{
    public function store(Request $request, User $user, StaffAuditLogger $audit): RedirectResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            abort(403);
        }

        if (TenantImpersonation::isActive()) {
            abort(403, 'Termina prima l\'accesso come allievo.');
        }

        if (! $user->isLearner() || $user->is($actor)) {
            abort(404);
        }

        $audit->logFromHttpExchange($actor, $request, null);

        $adminId = (string) $actor->getKey();
        Auth::login($user, remember: false);
        $request->session()->regenerate();
        $request->session()->put(TenantImpersonation::SESSION_KEY, $adminId);

        $name = $user->displayName() !== '' ? $user->displayName() : (string) $user->email;

        return redirect()
            ->route('tenant.dashboard')
            ->with('toast', 'Stai navigando come '.$name.'.');
    }

    public function destroy(Request $request, StaffAuditLogger $audit): RedirectResponse
    {
        $admin = $this->restoreAdmin($request);
        if (! $admin instanceof User) {
            return redirect()->route('tenant.login');
        }

        $audit->logFromHttpExchange($admin, $request, null);

        return redirect()
            ->route('tenant.admin.dashboard')
            ->with('toast', 'Sei tornato all\'area admin.');
    }

    private function restoreAdmin(Request $request): ?User
    {
        $adminId = TenantImpersonation::impersonatorId();
        if ($adminId === null) {
            abort(403);
        }

        $admin = User::query()->whereKey($adminId)->first();
        if ($admin === null || ! $admin->isStaffMember()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return null;
        }

        Auth::login($admin, remember: false);
        $request->session()->regenerate();
        $request->session()->forget(TenantImpersonation::SESSION_KEY);

        return $admin;
    }
}
