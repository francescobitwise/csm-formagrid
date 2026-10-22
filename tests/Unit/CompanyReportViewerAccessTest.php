<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\TenantPermission;
use App\Enums\UserRole;
use App\Models\Tenant\Course;
use App\Models\Tenant\User;
use App\Support\TenantPermissions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyReportViewerAccessTest extends TestCase
{
    #[Test]
    public function company_manager_can_only_read_course_reports(): void
    {
        $user = new User;
        $user->role = UserRole::CompanyManager;

        $this->assertTrue($user->isCompanyManager());
        $this->assertTrue($user->isStaffMember());
        $this->assertFalse($user->isContentStaff());
        $this->assertFalse($user->isLearner());

        $this->assertTrue(TenantPermissions::allows($user, TenantPermission::ContentCoursesRead));
        $this->assertFalse(TenantPermissions::allows($user, TenantPermission::ContentCoursesManage));
        $this->assertFalse(TenantPermissions::allows($user, TenantPermission::LearnersManage));
        $this->assertFalse(TenantPermissions::allows($user, TenantPermission::CompaniesManage));
        $this->assertFalse(TenantPermissions::allows($user, TenantPermission::AdminDashboard));
        $this->assertFalse(TenantPermissions::allows($user, TenantPermission::StaffManage));
        $this->assertFalse(TenantPermissions::allows($user, TenantPermission::ContentLessons));
    }

    #[Test]
    public function company_manager_without_a_company_cannot_open_a_course_report(): void
    {
        $user = new User;
        $user->role = UserRole::CompanyManager;
        $user->company_id = null;

        $course = new Course;
        $course->id = '11111111-1111-1111-1111-111111111111';

        $this->assertFalse($user->canReadCourseReports($course));
    }
}
