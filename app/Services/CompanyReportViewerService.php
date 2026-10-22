<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Tenant\Company;
use App\Models\Tenant\User;
use App\Notifications\TenantStaffCredentialsNotification;
use Illuminate\Support\Str;
use Throwable;

/**
 * Account in sola lettura legato all'email aziendale.
 */
final class CompanyReportViewerService
{
    /**
     * Crea il responsabile se manca, aggiorna nome ed email, e invia le credenziali
     * solo alla creazione o quando l'email cambia.
     *
     * @return array{user: User, credentials_sent: bool, provisioned: bool}
     */
    public function sync(Company $company, bool $sendCredentials = true): array
    {
        $email = strtolower(trim((string) $company->email));
        $name = $this->displayName($company, $email);

        $viewer = $company->reportViewer()->first();
        $created = $viewer === null;
        $emailChanged = $viewer instanceof User && strtolower((string) $viewer->email) !== $email;
        $plain = null;

        if ($viewer === null) {
            $plain = $this->temporaryPassword();
            $viewer = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $plain,
                'role' => UserRole::CompanyManager,
                'company_id' => $company->id,
                'email_verified_at' => now(),
                'must_change_password' => true,
            ]);
        } else {
            $viewer->name = $name;
            $viewer->email = $email;
            $viewer->company_id = $company->id;
            $viewer->role = UserRole::CompanyManager;

            if ($emailChanged) {
                $plain = $this->temporaryPassword();
                $viewer->password = $plain;
                $viewer->must_change_password = true;
                $viewer->remember_token = Str::random(60);
                $viewer->current_session_id = Str::random(40);
            }

            $viewer->save();
        }

        $shouldSend = $sendCredentials && $plain !== null;
        $sent = $shouldSend && $this->deliver($viewer, $plain);

        return [
            'user' => $viewer,
            'credentials_sent' => $sent,
            'provisioned' => $created || $emailChanged,
        ];
    }

    public function resend(User $viewer): bool
    {
        $plain = $this->temporaryPassword();
        $viewer->forceFill([
            'password' => $plain,
            'remember_token' => Str::random(60),
            'must_change_password' => true,
            'current_session_id' => Str::random(40),
        ])->save();

        return $this->deliver($viewer, $plain);
    }

    private function deliver(User $viewer, string $plain): bool
    {
        try {
            $viewer->notify(new TenantStaffCredentialsNotification(
                $plain,
                'Responsabile azienda (solo report dei propri allievi)',
            ));
            $viewer->forceFill(['credentials_sent_at' => now()])->save();

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    private function displayName(Company $company, string $email): string
    {
        $contact = trim((string) ($company->contact_name ?? ''));
        if ($contact !== '') {
            return $contact;
        }

        $legal = trim((string) ($company->legal_name ?: $company->name));

        return $legal !== '' ? $legal : $email;
    }

    private function temporaryPassword(): string
    {
        return Str::password(18, true, true, false, false);
    }
}
