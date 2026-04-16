<?php

namespace App\Http\Controllers\Tenant\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Company;
use App\Models\Tenant\User;
use App\Notifications\TenantLearnerCredentialsNotification;
use App\Services\LearnerCsvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class LearnerController extends Controller
{
    public function index(Request $request): View
    {
        $learners = User::query()
            ->where('role', UserRole::Learner)
            ->with('company')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('tenant.admin.learners.index', [
            'learners' => $learners,
        ]);
    }

    public function indexForCompany(Request $request, Company $company): View
    {
        $learners = User::query()
            ->where('role', UserRole::Learner)
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('tenant.admin.companies.learners.index', [
            'company' => $company,
            'learners' => $learners,
        ]);
    }

    public function create(): View
    {
        return view('tenant.admin.learners.create', [
            'companies' => Company::query()->orderBy('name')->get(),
        ]);
    }

    public function createForCompany(Company $company): View
    {
        return view('tenant.admin.companies.learners.create', [
            'company' => $company,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'company_id' => ['nullable', 'uuid', 'exists:companies,id'],
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
            'role' => UserRole::Learner,
            'company_id' => $data['company_id'] ?? null,
            'email_verified_at' => now(),
            'must_change_password' => $mustChangePassword,
        ]);

        if ($request->boolean('send_credentials_email')) {
            $user->notify(new TenantLearnerCredentialsNotification($plain));
            $user->update(['credentials_sent_at' => now()]);
        }

        return redirect()
            ->route('tenant.admin.learners.index')
            ->with('toast', 'Allievo creato.'.($request->boolean('send_credentials_email') ? ' Email con credenziali inviata.' : ''));
    }

    public function storeForCompany(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
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
            'role' => UserRole::Learner,
            'company_id' => $company->id,
            'email_verified_at' => now(),
            'must_change_password' => $mustChangePassword,
        ]);

        if ($request->boolean('send_credentials_email')) {
            $user->notify(new TenantLearnerCredentialsNotification($plain));
            $user->update(['credentials_sent_at' => now()]);
        }

        return redirect()
            ->route('tenant.admin.companies.learners.index', $company)
            ->with('toast', 'Allievo creato.'.($request->boolean('send_credentials_email') ? ' Email con credenziali inviata.' : ''));
    }

    public function importForm(): View
    {
        return view('tenant.admin.learners.import', [
        ]);
    }

    public function importStore(Request $request, LearnerCsvImportService $csvImport): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'send_credentials_email' => ['sometimes', 'boolean'],
        ]);

        $result = $csvImport->import(
            $request->file('csv_file'),
            $request->boolean('send_credentials_email')
        );

        if ($request->boolean('send_credentials_email')) {
            foreach ($result['users_to_notify'] as $user) {
                $plain = $result['plain_passwords_by_user_id'][$user->id] ?? null;
                if (is_string($plain) && $plain !== '') {
                    $user->notify(new TenantLearnerCredentialsNotification($plain));
                    $user->update(['credentials_sent_at' => now()]);
                }
            }
        }

        $parts = [
            "Creati: {$result['created']}.",
            "Saltati / errori riga: {$result['skipped']}.",
        ];
        if ($result['errors'] !== []) {
            $parts[] = implode(' ', array_slice($result['errors'], 0, 8));
            if (count($result['errors']) > 8) {
                $parts[] = '…';
            }
        }

        return redirect()
            ->route('tenant.admin.learners.import')
            ->with('toast', implode(' ', $parts));
    }

    public function importFormForCompany(Company $company): View
    {
        return view('tenant.admin.companies.learners.import', [
            'company' => $company,
        ]);
    }

    public function importStoreForCompany(Request $request, Company $company, LearnerCsvImportService $csvImport): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'send_credentials_email' => ['sometimes', 'boolean'],
        ]);

        $result = $csvImport->import(
            $request->file('csv_file'),
            $request->boolean('send_credentials_email'),
            (string) $company->id,
        );

        if ($request->boolean('send_credentials_email')) {
            foreach ($result['users_to_notify'] as $user) {
                $plain = $result['plain_passwords_by_user_id'][$user->id] ?? null;
                if (is_string($plain) && $plain !== '') {
                    $user->notify(new TenantLearnerCredentialsNotification($plain));
                    $user->update(['credentials_sent_at' => now()]);
                }
            }
        }

        $parts = [
            "Creati: {$result['created']}.",
            "Saltati / errori riga: {$result['skipped']}.",
        ];
        if ($result['errors'] !== []) {
            $parts[] = implode(' ', array_slice($result['errors'], 0, 8));
            if (count($result['errors']) > 8) {
                $parts[] = '…';
            }
        }

        return redirect()
            ->route('tenant.admin.companies.learners.import', $company)
            ->with('toast', implode(' ', $parts));
    }

    public function sendCredentials(User $user): RedirectResponse
    {
        $this->ensureLearner($user);

        $plain = Str::password(18, true, true, false, false);
        $user->update([
            'password' => $plain,
            'remember_token' => Str::random(60),
            'must_change_password' => true,
        ]);

        $user->notify(new TenantLearnerCredentialsNotification($plain));
        $user->update(['credentials_sent_at' => now()]);

        return back()->with('toast', 'Nuova password generata e inviata a '.$user->email.'.');
    }

    public function sendCredentialsBulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'learner_ids' => ['required', 'array', 'min:1', 'max:100'],
            'learner_ids.*' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $users = User::query()
            ->whereIn('id', $data['learner_ids'])
            ->where('role', UserRole::Learner)
            ->get();

        $sent = 0;
        foreach ($users as $user) {
            $plain = Str::password(18, true, true, false, false);
            $user->update([
                'password' => $plain,
                'remember_token' => Str::random(60),
                'must_change_password' => true,
            ]);
            $user->notify(new TenantLearnerCredentialsNotification($plain));
            $user->update(['credentials_sent_at' => now()]);
            $sent++;
        }

        return back()->with('toast', "Email inviate a {$sent} allievo/i (password rigenerate).");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureLearner($user);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Non puoi eliminare il tuo stesso account.']);
        }

        $user->delete();

        return redirect()
            ->route('tenant.admin.learners.index')
            ->with('toast', 'Allievo eliminato.');
    }

    private function ensureLearner(User $user): void
    {
        abort_unless($user->role === UserRole::Learner, 404);
    }
}
