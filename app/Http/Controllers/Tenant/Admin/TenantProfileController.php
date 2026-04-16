<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\TenantBranding;
use App\Support\UploadedFileStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TenantProfileController extends Controller
{
    private const string PROFILE_EDIT_ROUTE = 'tenant.admin.profile.edit';

    public function edit(): View
    {
        $t = tenant();
        $pdf = is_array($t?->pdf_course_report ?? null) ? $t->pdf_course_report : [];

        return view('tenant.admin.profile', [
            'logoUrl' => TenantBranding::logoUrl(),
            'organizationName' => (string) config('app.name', ''),
            'contactEmail' => old('contact_email', (string) ($t?->contact_email ?? '')),
            'pdfReportHeader' => old('pdf_report_header', (string) ($pdf['header'] ?? '')),
            'pdfReportFooter' => old('pdf_report_footer', (string) ($pdf['footer'] ?? '')),
            'pdfReportAccent' => old('pdf_report_accent', (string) ($pdf['accent'] ?? '')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge([
            'contact_email' => trim((string) $request->input('contact_email', '')) ?: null,
            'pdf_report_header' => trim((string) $request->input('pdf_report_header', '')),
            'pdf_report_footer' => trim((string) $request->input('pdf_report_footer', '')),
            'pdf_report_accent' => trim((string) $request->input('pdf_report_accent', '')) ?: null,
        ]);

        $validated = $request->validate([
            'contact_email' => ['nullable', 'email', 'max:255'],
            'pdf_report_header' => ['nullable', 'string', 'max:2000'],
            'pdf_report_footer' => ['nullable', 'string', 'max:2000'],
            'pdf_report_accent' => ['nullable', 'string', 'max:32', 'regex:/^#[0-9a-f]{6}$/i'],
        ]);

        Setting::put('contact_email', $validated['contact_email']);
        Setting::put('pdf_course_report', [
            'header' => (string) ($validated['pdf_report_header'] ?? ''),
            'footer' => (string) ($validated['pdf_report_footer'] ?? ''),
            'accent' => $validated['pdf_report_accent'] ?? null,
        ]);

        return redirect()
            ->route(self::PROFILE_EDIT_ROUTE)
            ->with('toast', 'Profilo organizzazione aggiornato.');
    }

    public function updateLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['nullable', 'file', 'max:2048', 'mimes:jpeg,jpg,png,gif,webp,svg'],
            'remove_logo' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('remove_logo')) {
            $this->removeLogo();

            return redirect()
                ->route(self::PROFILE_EDIT_ROUTE)
                ->with('toast', 'Logo rimosso. Verrà usato il simbolo predefinito.');
        }

        $redirect = redirect()->route(self::PROFILE_EDIT_ROUTE);
        if (! $request->hasFile('logo')) {
            return $redirect;
        }

        $result = $this->storeLogoFromRequest($request);

        return $result['ok']
            ? $redirect->with('toast', 'Logo aggiornato.')
            : $redirect->withErrors(['logo' => $result['error']]);
    }

    private function removeLogo(): void
    {
        $path = (string) (Setting::get('brand_logo', 'brand/logo.png') ?? 'brand/logo.png');
        if ($path !== '') {
            Storage::disk('public')->delete($path);
        }

        Setting::put('brand_logo', null);
    }

    /**
     * @return array{ok: bool, error: string|null}
     */
    private function storeLogoFromRequest(Request $request): array
    {
        $file = $request->file('logo');
        if ($file === null || ! $file->isValid()) {
            return ['ok' => false, 'error' => 'Upload non valido o file troppo grande.'];
        }

        $old = Setting::get('brand_logo');
        if (is_string($old) && $old !== '') {
            Storage::disk('public')->delete($old);
        }

        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'png'));
        $ext = $ext !== '' ? $ext : 'png';
        $relative = 'brand/logo.'.$ext;

        $stored = UploadedFileStorage::put($file, 'public', $relative, 'public');
        if ($stored === false) {
            return ['ok' => false, 'error' => 'Impossibile salvare il file. Riprova.'];
        }

        Setting::put('brand_logo', $stored);

        return ['ok' => true, 'error' => null];
    }
}

