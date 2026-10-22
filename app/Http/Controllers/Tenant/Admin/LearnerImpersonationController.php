<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Company;
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

        if (! $user->isLearner()) {
            abort(404);
        }

        return $this->impersonate($request, $actor, $user, 'tenant.dashboard', $audit);
    }

    public function storeCompany(Request $request, Company $company, StaffAuditLogger $audit): RedirectResponse
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            abort(403);
        }

        $viewer = $company->reportViewer()->first();
        if (! $viewer instanceof User || ! $viewer->isCompanyManager()) {
            abort(404);
        }

        return $this->impersonate($request, $actor, $viewer, 'tenant.admin.courses.index', $audit);
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
        $admin->forceFill(['current_session_id' => $request->session()->getId()])->save();

        return $admin;
    }

    private function impersonate(Request $request, User $actor, User $target, string $homeRoute, StaffAuditLogger $audit): RedirectResponse
    {
        if (TenantImpersonation::isActive()) {
            abort(403, 'Termina prima l\'accesso come altro utente.');
        }

        if ($target->is($actor)) {
            abort(404);
        }

        $audit->logFromHttpExchange($actor, $request, null);

        Auth::login($target, remember: false);
        $request->session()->regenerate();
        $request->session()->put(TenantImpersonation::SESSION_KEY, (string) $actor->getKey());
        $target->forceFill(['current_session_id' => $request->session()->getId()])->save();

        $name = $target->displayName() !== '' ? $target->displayName() : (string) $target->email;

        return redirect()
            ->route($homeRoute)
            ->with('toast', 'Stai navigando come '.$name.'.');
    }
}
