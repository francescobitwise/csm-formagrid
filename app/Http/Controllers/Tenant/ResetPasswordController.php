<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('tenant.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'must_change_password' => false,
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $request->session()->regenerate();

            return redirect()
                ->route('tenant.login')
                ->with('toast', 'Password aggiornata. Ora puoi accedere con le nuove credenziali.');
        }

        $message = match ($status) {
            Password::INVALID_TOKEN => 'Il link non è valido o è scaduto. Richiedi una nuova email di reset.',
            Password::INVALID_USER => 'Non abbiamo trovato un account con questa email.',
            Password::RESET_THROTTLED => 'Attendi prima di riprovare.',
            default => 'Impossibile aggiornare la password. Riprova.',
        };

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $message]);
    }
}
