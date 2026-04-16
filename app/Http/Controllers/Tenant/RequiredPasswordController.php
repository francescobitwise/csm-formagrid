<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\TenantPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

final class RequiredPasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('tenant.auth.required-password', [
            'email' => $request->user()->email,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        $default = TenantPermissions::staff($user)
            ? route('tenant.admin.dashboard')
            : route('tenant.dashboard');

        return redirect()->to($default)->with('toast', 'Password aggiornata. Benvenuto.');
    }
}
