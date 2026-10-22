<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Company;
use App\Models\Tenant\User;
use App\Services\CompanyReportViewerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $companies = Company::query()
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->withCount([
                'users as learners_count' => fn ($query) => $query->where('role', UserRole::Learner),
            ])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('tenant.admin.companies.index', [
            'companies' => $companies,
            'q' => $q,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('tenant.admin.companies.index', ['create' => 1]);
    }

    public function show(Company $company): View
    {
        $company->loadCount([
            'users as learners_count' => fn ($query) => $query->where('role', UserRole::Learner),
        ]);
        $company->load('reportViewer');

        return view('tenant.admin.companies.show', [
            'company' => $company,
        ]);
    }

    public function store(Request $request, CompanyReportViewerService $viewers): RedirectResponse
    {
        $data = $this->validated($request);

        $company = DB::transaction(fn () => Company::query()->create($data));
        $result = $viewers->sync($company, sendCredentials: true);

        return redirect()
            ->route('tenant.admin.companies.index')
            ->with('toast', $this->savedToast('Azienda creata.', $result, (string) $company->email));
    }

    public function edit(Company $company): View
    {
        return view('tenant.admin.companies.form', [
            'company' => $company,
        ]);
    }

    public function update(Request $request, Company $company, CompanyReportViewerService $viewers): RedirectResponse
    {
        $data = $this->validated($request, $company);

        $company->update($data);
        $result = $viewers->sync($company->fresh() ?? $company, sendCredentials: true);

        return redirect()
            ->route('tenant.admin.companies.index')
            ->with('toast', $this->savedToast('Azienda aggiornata.', $result, (string) $company->email));
    }

    public function sendReportAccess(Company $company, CompanyReportViewerService $viewers): RedirectResponse
    {
        if (! is_string($company->email) || trim($company->email) === '') {
            return back()->withErrors([
                'email' => 'Indica prima l\'email aziendale: è l\'accesso ai report.',
            ]);
        }

        $viewer = $company->reportViewer()->first();
        if ($viewer === null) {
            $result = $viewers->sync($company, sendCredentials: true);
            $sent = $result['credentials_sent'];
        } else {
            $sent = $viewers->resend($viewer);
        }

        return back()->with('toast', $sent
            ? 'Credenziali inviate a '.$company->email.'.'
            : 'Account pronto, ma l\'email non è partita. Riprova tra poco.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        if ($company->learners()->exists()) {
            return back()->withErrors([
                'company' => 'Non puoi eliminare un’azienda che ha corsisti associati. Sposta prima i corsisti (o rimuovi l’associazione).',
            ]);
        }

        $company->reportViewer()->delete();
        $company->delete();

        return redirect()
            ->route('tenant.admin.companies.index')
            ->with('toast', 'Azienda eliminata.');
    }

    /**
     * @param  array{user: User, credentials_sent: bool, provisioned: bool}  $result
     */
    private function savedToast(string $base, array $result, string $email): string
    {
        if (! $result['provisioned']) {
            return $base;
        }

        if ($result['credentials_sent']) {
            return $base.' Credenziali report inviate a '.$email.'.';
        }

        return $base.' Account report creato, ma l\'email non è partita. Reinviala dalla scheda azienda.';
    }

    /**
     * @return array{
     *   name: string,
     *   legal_name: string,
     *   vat: string,
     *   email: string,
     *   phone: string|null,
     *   contact_name: string|null,
     *   address_line1: string|null,
     *   address_line2: string|null,
     *   postal_code: string|null,
     *   city: string|null,
     *   province: string|null,
     *   country: string|null,
     *   notes: string|null
     * }
     */
    private function validated(Request $request, ?Company $company = null): array
    {
        $legalName = trim((string) $request->input('legal_name', ''));
        $request->merge([
            // Product rule: company "name" is the legal name (ragione sociale)
            'legal_name' => $legalName,
            'name' => $legalName,
            'vat' => trim((string) $request->input('vat', '')),
            'email' => strtolower(trim((string) $request->input('email', ''))),
            'phone' => trim((string) $request->input('phone', '')) ?: null,
            'contact_name' => trim((string) $request->input('contact_name', '')) ?: null,
            'address_line1' => trim((string) $request->input('address_line1', '')) ?: null,
            'address_line2' => trim((string) $request->input('address_line2', '')) ?: null,
            'postal_code' => trim((string) $request->input('postal_code', '')) ?: null,
            'city' => trim((string) $request->input('city', '')) ?: null,
            'province' => trim((string) $request->input('province', '')) ?: null,
            'country' => strtoupper(trim((string) $request->input('country', ''))) ?: null,
            'notes' => trim((string) $request->input('notes', '')) ?: null,
        ]);

        $managerId = $company?->reportViewer()->value('id');

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['required', 'string', 'max:255'],
            'vat' => ['required', 'string', 'max:32'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('companies', 'email')->ignore($company?->id),
                Rule::unique('users', 'email')->ignore($managerId),
            ],
            'phone' => ['nullable', 'string', 'max:64'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:128'],
            'province' => ['nullable', 'string', 'max:64'],
            'country' => ['nullable', 'string', 'max:2'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'email.required' => 'Indica l\'email aziendale: è l\'accesso ai report degli allievi.',
            'email.email' => 'L\'email aziendale non è valida.',
            'email.unique' => 'Questa email è già usata da un\'altra azienda o da un altro utente.',
        ]);
    }
}
