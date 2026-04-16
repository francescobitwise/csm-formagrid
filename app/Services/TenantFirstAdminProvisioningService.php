<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Landlord\Tenant;
use App\Models\Tenant\User;
use App\Notifications\TenantStaffCredentialsNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Crea il primo utente Admin nel DB tenant e invia le credenziali via email (idempotente).
 */
final class TenantFirstAdminProvisioningService
{
    /**
     * Se non esiste ancora un admin, crea l’account e invia la mail. Ignora errori di invio (log).
     *
     * @return bool true se è stato creato un nuovo admin
     */
    public function ensureFirstAdmin(Tenant $tenant, string $adminEmail, string $adminDisplayName): bool
    {
        $adminEmail = strtolower(trim($adminEmail));
        if (! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            Log::warning('tenant.first_admin.skip_invalid_email', [
                'tenant_id' => $tenant->getKey(),
                'email' => $adminEmail,
            ]);

            return false;
        }

        $adminDisplayName = trim($adminDisplayName) !== '' ? trim($adminDisplayName) : 'Amministratore';
        $adminDisplayName = Str::limit($adminDisplayName, 120, '');

        $domain = $tenant->domains->first()?->domain;
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $loginUrl = $domain !== null && $domain !== ''
            ? $scheme.'://'.$domain.'/login'
            : null;

        $created = false;
        $landlordTenantId = $tenant->getKey();

        try {
            $tenant->run(function () use ($landlordTenantId, $adminEmail, $adminDisplayName, $loginUrl, &$created): void {
                if (User::query()->where('role', UserRole::Admin)->exists()) {
                    return;
                }

                // Solo lettere e numeri: simboli tipo <>& nelle email HTML spesso copiati male dal client di posta.
                $plain = Str::password(18, true, true, false, false);

                $user = User::query()->create([
                    'name' => $adminDisplayName,
                    'email' => $adminEmail,
                    'password' => $plain,
                    'role' => UserRole::Admin,
                    'email_verified_at' => now(),
                    'must_change_password' => true,
                ]);

                try {
                    $user->notify(new TenantStaffCredentialsNotification(
                        $plain,
                        'Amministratore',
                        $loginUrl,
                    ));
                    $user->update(['credentials_sent_at' => now()]);
                } catch (Throwable $mailError) {
                    Log::error('tenant.first_admin.mail_failed', [
                        'tenant_id' => $landlordTenantId,
                        'user_id' => $user->getKey(),
                        'message' => $mailError->getMessage(),
                    ]);
                }

                $created = true;
            });
        } catch (Throwable $e) {
            Log::error('tenant.first_admin.failed', [
                'tenant_id' => $tenant->getKey(),
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        return $created;
    }
}
