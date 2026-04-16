<?php

namespace App\Support;

use App\Enums\TenantPermission;
use App\Enums\UserRole;
use App\Models\Tenant\User;

final class TenantPermissions
{
    public static function allows(?User $user, TenantPermission|string $permission): bool
    {
        if ($user === null) {
            return false;
        }

        $key = $permission instanceof TenantPermission ? $permission->value : $permission;

        if ($user->isLearner()) {
            return false;
        }

        if ($user->tenantRoleValue() === UserRole::Admin->value) {
            return true;
        }

        $map = config('tenant_permissions.'.$user->tenantRoleValue(), []);

        if (in_array('*', $map, true)) {
            return true;
        }

        return in_array($key, $map, true);
    }

    public static function staff(User $user): bool
    {
        return $user->isStaffMember();
    }
}
