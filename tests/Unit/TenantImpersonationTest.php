<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\TenantImpersonation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantImpersonationTest extends TestCase
{
    #[Test]
    public function the_flag_is_active_only_when_the_session_holds_an_admin_id(): void
    {
        $this->assertFalse(TenantImpersonation::isActive());
        $this->assertNull(TenantImpersonation::impersonatorId());

        session([TenantImpersonation::SESSION_KEY => 'admin-id']);

        $this->assertTrue(TenantImpersonation::isActive());
        $this->assertSame('admin-id', TenantImpersonation::impersonatorId());
    }
}
