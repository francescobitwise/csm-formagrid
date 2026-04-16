<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Admin;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Tenant;
use App\Services\CustomDomainService;
use App\Services\TenantQuotaService;
use App\Support\TenantBranding;
use App\Support\UploadedFileStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\Process\Process;

class TenantProfileController extends Controller
{
    private const string PROFILE_EDIT_ROUTE = 'tenant.admin.profile.edit';

    public function edit(TenantQuotaService $quota): View
    {
        /** @var Tenant $t */
        $t = tenant();
        $t->loadMissing('domains');

        $pdf = is_array($t->pdf_course_report) ? $t->pdf_course_report : [];

        return view('tenant.admin.profile', [
            'logoUrl' => TenantBranding::logoUrl(),
            'organizationName' => old('organization_name', $t->organization_name),
            'contactEmail' => old('contact_email', $t->contact_email),
            'pdfReportHeader' => old('pdf_report_header', (string) ($pdf['header'] ?? '')),
            'pdfReportFooter' => old('pdf_report_footer', (string) ($pdf['footer'] ?? '')),
            'pdfReportAccent' => old('pdf_report_accent', (string) ($pdf['accent'] ?? '')),
            'domains' => $t->domains,
            'allowsCustomDomain' => $quota->allowsCustomDomain(),
            'centralDomain' => (string) config('app.central_domain', env('CENTRAL_DOMAIN', 'localhost')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge([
            'organization_name' => trim((string) $request->input('organization_name', '')) ?: null,
            'contact_email' => trim((string) $request->input('contact_email', '')) ?: null,
            'pdf_report_header' => trim((string) $request->input('pdf_report_header', '')),
            'pdf_report_footer' => trim((string) $request->input('pdf_report_footer', '')),
            'pdf_report_accent' => trim((string) $request->input('pdf_report_accent', '')) ?: null,
        ]);

        $validated = $request->validate([
            'organization_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'pdf_report_header' => ['nullable', 'string', 'max:2000'],
            'pdf_report_footer' => ['nullable', 'string', 'max:2000'],
            'pdf_report_accent' => ['nullable', 'string', 'max:32', 'regex:/^#[0-9a-f]{6}$/i'],
        ]);

        /** @var Tenant $tenant */
        $tenant = tenant();
        $tenant->organization_name = $validated['organization_name'];
        $tenant->contact_email = $validated['contact_email'];
        $tenant->pdf_course_report = [
            'header' => (string) ($validated['pdf_report_header'] ?? ''),
            'footer' => (string) ($validated['pdf_report_footer'] ?? ''),
            'accent' => (string) ($validated['pdf_report_accent'] ?? ''),
        ];

        $tenant->save();

        return redirect()
            ->route('tenant.admin.profile.edit')
            ->with('toast', 'Dati organizzazione salvati nel profilo del tenant.');
    }

    public function updateLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['nullable', 'file', 'max:2048', 'mimes:jpeg,jpg,png,gif,webp,svg'],
            'remove_logo' => ['sometimes', 'boolean'],
        ]);

        /** @var Tenant $tenant */
        $tenant = tenant();

        if ($request->boolean('remove_logo')) {
            $this->removeTenantLogo($tenant);

            return redirect()
                ->route(self::PROFILE_EDIT_ROUTE)
                ->with('toast', 'Logo rimosso. Verrà usato il simbolo predefinito.');
        }

        $redirect = redirect()->route(self::PROFILE_EDIT_ROUTE);
        if (! $request->hasFile('logo')) {
            return $redirect;
        }

        $result = $this->storeTenantLogoFromRequest($request, $tenant);

        return $result['ok']
            ? $redirect->with('toast', 'Logo aggiornato.')
            : $redirect->withErrors(['logo' => $result['error']]);
    }

    public function addCustomDomain(Request $request, TenantQuotaService $quota): RedirectResponse
    {
        if (! $quota->allowsCustomDomain()) {
            return back()->withErrors([
                'custom_domain' => 'Il tuo piano non include il dominio personalizzato.',
            ]);
        }

        $request->merge([
            'custom_domain' => $this->normalizeDomain((string) $request->input('custom_domain', '')),
        ]);

        $centralDomain = (string) config('app.central_domain', env('CENTRAL_DOMAIN', 'localhost'));

        $validated = $request->validate([
            'custom_domain' => [
                'required',
                'string',
                'max:255',
                // host only (no scheme, no path, no port)
                'regex:/^(?=.{1,255}$)(?!-)(?:[a-z0-9-]{1,63}\.)+[a-z0-9-]{2,63}$/i',
                Rule::notIn([$centralDomain]),
                Rule::notIn((array) config('tenancy.central_domains', [])),
                Rule::unique('domains', 'domain'),
            ],
        ]);

        /** @var Tenant $tenant */
        $tenant = tenant();

        // Non blocchiamo il dominio "default" del tenant: aggiungiamo un dominio in più (custom).
        $tenant->domains()->create([
            'domain' => $validated['custom_domain'],
        ]);

        return redirect()
            ->route(self::PROFILE_EDIT_ROUTE)
            ->with('toast', 'Dominio personalizzato aggiunto. Configura DNS/SSL e poi apri la piattaforma da quel dominio.');
    }

    public function removeCustomDomain(Request $request, string $domain): RedirectResponse
    {
        $domain = $this->normalizeDomain($domain);

        /** @var Tenant $tenant */
        $tenant = tenant();
        $tenant->loadMissing('domains');

        $centralDomain = (string) config('app.central_domain', env('CENTRAL_DOMAIN', 'localhost'));
        $defaultDomain = "{$tenant->id}.{$centralDomain}";

        if ($domain === $defaultDomain) {
            return back()->withErrors([
                'custom_domain' => 'Non puoi rimuovere il dominio predefinito del tenant.',
            ]);
        }

        $deleted = $tenant->domains()->where('domain', $domain)->delete();
        if ($deleted <= 0) {
            return back()->withErrors([
                'custom_domain' => 'Dominio non trovato o non associato a questo tenant.',
            ]);
        }

        return redirect()
            ->route(self::PROFILE_EDIT_ROUTE)
            ->with('toast', 'Dominio rimosso.');
    }

    public function checkCustomDomain(Request $request, string $domain, CustomDomainService $service): RedirectResponse
    {
        $domain = $this->normalizeDomain($domain);
        $expectedTenantId = (string) (tenant('id') ?? '');

        $result = $service->check($domain, $expectedTenantId);

        // HTTPS required: when DNS is ready we provision SSL automatically (if enabled).
        $provision = ['attempted' => false, 'ok' => false, 'details' => null];
        if ((bool) data_get($result, 'dns.ok')) {
            $provision = $this->tryProvisionSsl($domain);

            // Re-check after provisioning attempt to reflect HTTPS status.
            $result = $service->check($domain, $expectedTenantId);
        }

        $result['https_required'] = true;
        $result['provision'] = $provision;

        return redirect()
            ->route(self::PROFILE_EDIT_ROUTE)
            ->with('domain_check', $result);
    }

    /**
     * @return array{attempted: bool, ok: bool, details: string|null}
     */
    private function tryProvisionSsl(string $domain): array
    {
        $enabled = (bool) env('CUSTOM_DOMAIN_PROVISIONING_ENABLED', false);
        $script = trim((string) env('CUSTOM_DOMAIN_PROVISION_SCRIPT', ''));
        $precheckError = null;
        if (! $enabled) {
            $precheckError = 'Provisioning automatico non abilitato sul server.';
        } elseif ($script === '') {
            $precheckError = 'Script provisioning non configurato (CUSTOM_DOMAIN_PROVISION_SCRIPT).';
        }
        if (is_string($precheckError)) {
            return ['attempted' => false, 'ok' => false, 'details' => $precheckError];
        }

        $cmd = [$script, $domain];
        if ((bool) env('CUSTOM_DOMAIN_PROVISION_USE_SUDO', true)) {
            $cmd = array_merge(['sudo', '--'], $cmd);
        }

        $p = new Process($cmd);
        $p->setTimeout((int) env('CUSTOM_DOMAIN_PROVISION_TIMEOUT', 180));
        $p->run();

        $ok = $p->isSuccessful();
        $details = 'OK';
        if (! $ok) {
            $out = trim($p->getErrorOutput().' '.$p->getOutput());
            $details = $out !== '' ? mb_substr($out, 0, 1200) : 'Errore sconosciuto.';
        }

        return ['attempted' => true, 'ok' => $ok, 'details' => $details];
    }

    private function removeTenantLogo(Tenant $tenant): void
    {
        if ($old = $tenant->brand_logo) {
            Storage::disk('public')->delete($old);
        }
        $tenant->brand_logo = null;
        $tenant->save();
    }

    /**
     * @return array{ok: bool, error: string|null}
     */
    private function storeTenantLogoFromRequest(Request $request, Tenant $tenant): array
    {
        $file = $request->file('logo');
        if ($file === null || ! $file->isValid()) {
            return ['ok' => false, 'error' => 'Upload non valido o file troppo grande.'];
        }

        if ($old = $tenant->brand_logo) {
            Storage::disk('public')->delete($old);
        }

        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'png'));
        $ext = $ext !== '' ? $ext : 'png';

        $relative = 'brand/logo.'.$ext;

        $stored = UploadedFileStorage::put($file, 'public', $relative, 'public');
        if ($stored === false) {
            return ['ok' => false, 'error' => 'Impossibile salvare il file. Riprova.'];
        }

        $tenant->brand_logo = $stored;
        $tenant->save();

        return ['ok' => true, 'error' => null];
    }

    private function normalizeDomain(string $value): string
    {
        $v = strtolower(trim($value));
        $v = preg_replace('#^https?://#i', '', $v) ?? $v;
        $v = trim($v);
        $v = explode('/', $v, 2)[0];
        $v = explode('?', $v, 2)[0];
        $v = explode('#', $v, 2)[0];
        // strip port if present
        $v = explode(':', $v, 2)[0];

        return trim($v, ". \t\n\r\0\x0B");
    }
}
