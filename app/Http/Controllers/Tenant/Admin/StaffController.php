<?php

namespace App\Http\Controllers\Tenant\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use App\Notifications\TenantStaffCredentialsNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $staff = User::query()
            ->whereIn('role', [UserRole::Admin, UserRole::Instructor])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('tenant.admin.staff.index', compact('staff'));
    }

    public function create(): View
    {
        return view('tenant.admin.staff.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in([UserRole::Admin->value, UserRole::Instructor->value])],
            'password' => ['nullable', 'string', Password::defaults()],
            'send_credentials_email' => ['sometimes', 'boolean'],
        ]);

        $plain = $data['password'] ?? '';
        $mustChangePassword = ($plain === '');
        if ($plain === '') {
            $plain = Str::password(18, true, true, false, false);
        }

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $plain,
            'role' => UserRole::from($data['role']),
            'email_verified_at' => now(),
            'must_change_password' => $mustChangePassword,
        ]);

        if ($request->boolean('send_credentials_email')) {
            $user->notify(new TenantStaffCredentialsNotification($plain, $this->roleLabel($user->role)));
            $user->update(['credentials_sent_at' => now()]);
        }

        return redirect()
            ->route('tenant.admin.staff.index')
            ->with('toast', 'Utente staff creato.'.($request->boolean('send_credentials_email') ? ' Email inviata.' : ''));
    }

    public function sendCredentials(User $user): RedirectResponse
    {
        $this->ensureStaffUser($user);

        $plain = Str::password(18, true, true, false, false);
        $user->update([
            'password' => $plain,
            'remember_token' => Str::random(60),
            'must_change_password' => true,
        ]);

        $user->notify(new TenantStaffCredentialsNotification($plain, $this->roleLabel($user->role)));
        $user->update(['credentials_sent_at' => now()]);

        return back()->with('toast', 'Nuova password generata e inviata a '.$user->email.'.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureStaffUser($user);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Non puoi eliminare il tuo stesso account.']);
        }

        if ($user->role === UserRole::Admin) {
            $admins = User::query()->where('role', UserRole::Admin)->count();
            if ($admins <= 1) {
                return back()->withErrors(['user' => 'Deve restare almeno un amministratore del tenant.']);
            }
        }

        $user->delete();

        return redirect()
            ->route('tenant.admin.staff.index')
            ->with('toast', 'Utente staff eliminato.');
    }

    private function ensureStaffUser(User $user): void
    {
        abort_unless(in_array($user->role, [UserRole::Admin, UserRole::Instructor], true), 404);
    }

    private function roleLabel(UserRole $role): string
    {
        return match ($role) {
            UserRole::Admin => 'Amministratore',
            UserRole::Instructor => 'Istruttore (contenuti)',
            default => 'Staff',
        };
    }
}
