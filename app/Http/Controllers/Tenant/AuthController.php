<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\TenantPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function create()
    {
        return view('tenant.auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $credentials['email'] = strtolower(trim($credentials['email']));

        if (Auth::attempt($credentials, (bool) $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->must_change_password) {
                return redirect()->route('tenant.password.required');
            }

            $default = TenantPermissions::staff($user)
                ? route('tenant.admin.dashboard')
                : route('tenant.dashboard');

            return redirect()->intended($default);
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'Credenziali non valide.']);

    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.home');
    }
}
