<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $companies = Company::query()
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->withCount('users')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('tenant.admin.companies.index', [
            'companies' => $companies,
            'q' => $q,
        ]);
    }

    public function create(): View
    {
        return view('tenant.admin.companies.form', [
            'company' => null,
        ]);
    }

    public function show(Company $company): View
    {
        $company->loadCount('users');

        return view('tenant.admin.companies.show', [
            'company' => $company,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Company::query()->create($data);

        return redirect()
            ->route('tenant.admin.companies.index')
            ->with('toast', 'Azienda creata.');
    }

    public function edit(Company $company): View
    {
        return view('tenant.admin.companies.form', [
            'company' => $company,
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $this->validated($request);

        $company->update($data);

        return redirect()
            ->route('tenant.admin.companies.index')
            ->with('toast', 'Azienda aggiornata.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        if ($company->users()->exists()) {
            return back()->withErrors([
                'company' => 'Non puoi eliminare un’azienda che ha corsisti associati. Sposta prima i corsisti (o rimuovi l’associazione).',
            ]);
        }

        $company->delete();

        return redirect()
            ->route('tenant.admin.companies.index')
            ->with('toast', 'Azienda eliminata.');
    }

    /**
     * @return array{
     *   name: string,
     *   legal_name: string|null,
     *   vat: string|null,
     *   email: string|null,
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
    private function validated(Request $request): array
    {
        $request->merge([
            'name' => trim((string) $request->input('name', '')),
            'legal_name' => trim((string) $request->input('legal_name', '')) ?: null,
            'vat' => trim((string) $request->input('vat', '')) ?: null,
            'email' => trim((string) $request->input('email', '')) ?: null,
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

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'vat' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:128'],
            'province' => ['nullable', 'string', 'max:64'],
            'country' => ['nullable', 'string', 'max:2'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}

