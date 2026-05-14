<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_and_core_resource_pages_render_for_platform_admin(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        foreach ([
            '/admin',
            '/admin/schools',
            '/admin/users',
        ] as $path) {
            $this
                ->actingAs($admin)
                ->get($path)
                ->assertOk();
        }

        foreach ([
            '/admin/academic-years',
            '/admin/students',
            '/admin/staff',
            '/admin/student-attendances',
            '/admin/fee-types',
            '/admin/student-invoices',
            '/admin/exams',
            '/admin/report-cards',
        ] as $path) {
            $this
                ->actingAs($admin)
                ->get($path)
                ->assertNotFound();
        }

        $this
            ->actingAs($admin)
            ->get("/admin/users/{$admin->getKey()}")
            ->assertOk();
    }

    public function test_inactive_user_cannot_access_admin_panel(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $admin->update(['is_active' => false]);

        $this
            ->actingAs($admin)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_school_admin_can_access_school_portal_and_is_blocked_from_platform_resources(): void
    {
        $this->seed();

        $schoolAdmin = User::query()->where('email', 'principal@demo-school.test')->firstOrFail();
        $school = $schoolAdmin->schools()->firstOrFail();
        $tenantSlug = $school->slug;
        $staff = Staff::query()->where('school_id', $school->getKey())->firstOrFail();
        $student = Student::query()->where('school_id', $school->getKey())->firstOrFail();

        foreach ([
            "/portal/{$tenantSlug}",
            "/portal/{$tenantSlug}/school-classes",
            "/portal/{$tenantSlug}/students",
            "/portal/{$tenantSlug}/students/{$student->getKey()}",
            "/portal/{$tenantSlug}/staff",
            "/portal/{$tenantSlug}/staff/{$staff->getKey()}",
            "/portal/{$tenantSlug}/users",
            "/portal/{$tenantSlug}/student-invoices",
            "/portal/{$tenantSlug}/exams",
        ] as $path) {
            $this
                ->actingAs($schoolAdmin)
                ->get($path)
                ->assertOk();
        }

        $this
            ->actingAs($schoolAdmin)
            ->get("/portal/{$tenantSlug}/profile")
            ->assertNotFound();

        $this
            ->actingAs($schoolAdmin)
            ->get("/portal/{$tenantSlug}/schools")
            ->assertForbidden();
    }

    public function test_school_user_is_redirected_from_admin_to_their_school_portal(): void
    {
        $this->seed();

        $schoolAdmin = User::query()->where('email', 'principal@demo-school.test')->firstOrFail();
        $tenantSlug = $schoolAdmin->schools()->value('slug');

        foreach ([
            '/admin',
            '/admin/users',
        ] as $path) {
            $this
                ->actingAs($schoolAdmin)
                ->get($path)
                ->assertRedirect("/portal/{$tenantSlug}");
        }
    }

    public function test_school_portal_hides_school_selector_on_tenant_forms(): void
    {
        $this->seed();

        $schoolAdmin = User::query()->where('email', 'principal@demo-school.test')->firstOrFail();
        $tenantSlug = $schoolAdmin->schools()->value('slug');

        $this
            ->actingAs($schoolAdmin)
            ->get("/portal/{$tenantSlug}/academic-years/create")
            ->assertOk()
            ->assertDontSee('name="data.school_id"', escape: false);

        $this
            ->actingAs($schoolAdmin)
            ->get("/portal/{$tenantSlug}/terms/create")
            ->assertOk()
            ->assertDontSee('name="data.position"', escape: false);
    }

    public function test_teacher_user_only_accesses_teacher_workspace_and_teacher_resources(): void
    {
        $this->seed();

        $schoolAdmin = User::query()->where('email', 'principal@demo-school.test')->firstOrFail();
        $school = $schoolAdmin->schools()->firstOrFail();
        $tenantSlug = $school->slug;

        $teacher = User::query()->create([
            'name' => 'Teacher User',
            'email' => 'teacher-login@example.com',
            'password' => Hash::make('password'),
            'is_platform_admin' => false,
            'is_active' => true,
        ]);

        $teacher->schools()->syncWithoutDetaching([
            $school->getKey() => [
                'role' => 'teacher',
                'is_primary' => false,
            ],
        ]);

        Staff::query()
            ->where('school_id', $school->getKey())
            ->firstOrFail()
            ->update(['user_id' => $teacher->getKey(), 'staff_type' => Staff::TYPE_TEACHING]);

        foreach ([
            "/portal/{$tenantSlug}/my-teaching",
            "/portal/{$tenantSlug}/class-subjects",
            "/portal/{$tenantSlug}/student-scores",
            "/portal/{$tenantSlug}/report-cards",
        ] as $path) {
            $this
                ->actingAs($teacher)
                ->get($path)
                ->assertOk();
        }

        foreach ([
            "/portal/{$tenantSlug}/staff",
            "/portal/{$tenantSlug}/students",
            "/portal/{$tenantSlug}/school-classes",
            "/portal/{$tenantSlug}/fee-types",
            "/portal/{$tenantSlug}/student-invoices",
        ] as $path) {
            $this
                ->actingAs($teacher)
                ->get($path)
                ->assertForbidden();
        }
    }
}
