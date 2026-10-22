<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Sessione in cui un admin sta navigando con l'account di un allievo o di un'azienda.
 */
final class TenantImpersonation
{
    public const SESSION_KEY = 'tenant_impersonator_id';

    public static function impersonatorId(): ?string
    {
        $id = session(self::SESSION_KEY);

        return is_string($id) && $id !== '' ? $id : null;
    }

    public static function isActive(): bool
    {
        return self::impersonatorId() !== null;
    }
}
