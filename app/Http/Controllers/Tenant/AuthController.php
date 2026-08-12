<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\TenantPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

        $remember = (bool) $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            /** @var \App\Models\Tenant\User|null $user */
            $user = Auth::user();
            if ($user) {
                $user->increment('login_count');
                $user->forceFill([
                    'last_login_at' => now(),
                    'current_session_id' => $request->session()->getId(),
                    'remember_token' => Str::random(60),
                ])->save();

                // Riemette il cookie "ricordami" con il token ruotato (invalida gli altri dispositivi).
                if ($remember) {
                    Auth::login($user, true);
                }
            }

            if ($user->must_change_password) {
                return redirect()->route('tenant.password.required');
            }

            $default = TenantPermissions::staff($user)
                ? ($user->isInspector()
                    ? route('tenant.admin.courses.index')
                    : route('tenant.admin.dashboard'))
                : route('tenant.dashboard');

            return redirect()->intended($default);
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'Credenziali non valide.']);

    }

    public function destroy(Request $request)
    {
        $idleVideo = $request->input('reason') === 'idle_video';

        /** @var \App\Models\Tenant\User|null $user */
        $user = Auth::user();
        if ($user) {
            $user->forceFill(['current_session_id' => null])->save();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($idleVideo) {
            return redirect()
                ->route('tenant.login')
                ->with('idle_logout', 'Sessione chiusa per inattività sul video.');
        }

        return redirect()->route('tenant.home');
    }
}
