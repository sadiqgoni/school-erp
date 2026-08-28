<?php

namespace Tests\Feature;

use App\Models\School;
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
            $this->portalUrl($tenantSlug),
            $this->portalUrl($tenantSlug, '/school-classes'),
            $this->portalUrl($tenantSlug, '/students'),
            $this->portalUrl($tenantSlug, "/students/{$student->getKey()}"),
            $this->portalUrl($tenantSlug, '/staff'),
            $this->portalUrl($tenantSlug, "/staff/{$staff->getKey()}"),
            $this->portalUrl($tenantSlug, '/users'),
            $this->portalUrl($tenantSlug, '/student-invoices'),
            $this->portalUrl($tenantSlug, '/exams'),
        ] as $path) {
            $this
                ->actingAs($schoolAdmin)
                ->get($path)
                ->assertOk();
        }

        $this
            ->actingAs($schoolAdmin)
            ->get($this->portalUrl($tenantSlug, '/profile'))
            ->assertNotFound();

        $this
            ->actingAs($schoolAdmin)
            ->get($this->portalUrl($tenantSlug, '/schools'))
            ->assertForbidden();
    }

    public function test_student_table_searches_name_parts_without_full_name_column(): void
    {
        $this->seed();

        $schoolAdmin = User::query()->where('email', 'principal@demo-school.test')->firstOrFail();
        $tenantSlug = $schoolAdmin->schools()->value('slug');

        $this
            ->actingAs($schoolAdmin)
            ->get($this->portalUrl($tenantSlug, '/students?search=Aisha'))
            ->assertOk()
            ->assertSee('Aisha');
    }

    public function test_superadmin_can_open_any_school_portal_from_admin(): void
    {
        $this->seed();

        $superadmin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $allowedSchool = School::query()->create([
            'name' => 'Allowed School',
            'code' => 'ALLOW-NUR',
            'slug' => 'allowed-school-nursery',
            'division' => School::DIVISION_NURSERY,
            'email' => 'allowed@example.com',
            'phone' => '+2348000000011',
            'address' => 'Allowed address',
            'city' => 'Maiduguri',
            'state' => 'Borno',
            'country' => 'Nigeria',
            'is_active' => true,
        ]);

        $blockedSchool = School::query()->create([
            'name' => 'Blocked School',
            'code' => 'BLOCK-NUR',
            'slug' => 'blocked-school-nursery',
            'division' => School::DIVISION_NURSERY,
            'email' => 'blocked@example.com',
            'phone' => '+2348000000012',
            'address' => 'Blocked address',
            'city' => 'Maiduguri',
            'state' => 'Borno',
            'country' => 'Nigeria',
            'is_active' => true,
        ]);

        $superadmin->schools()->sync([
            $allowedSchool->getKey() => [
                'role' => User::ROLE_SUPERADMIN,
                'is_primary' => true,
            ],
        ]);

        $this->assertTrue($superadmin->fresh()->isSuperAdmin());
        $this->assertTrue($superadmin->fresh()->canAccessTenant($allowedSchool));
        $this->assertTrue($superadmin->fresh()->canAccessTenant($blockedSchool));

        $this
            ->actingAs($superadmin)
            ->get($this->portalUrl($blockedSchool->slug))
            ->assertOk();
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
                ->assertRedirect($this->portalUrl($tenantSlug));
        }
    }

    public function test_school_admin_linked_to_main_school_record_can_access_school_portal(): void
    {
        User::query()->create([
            'name' => 'Platform Admin',
            'email' => 'platform@example.com',
            'password' => Hash::make('password'),
            'is_platform_admin' => true,
            'is_active' => true,
        ]);

        $school = School::query()->create([
            'name' => 'Main Record School',
            'code' => 'MAIN-001',
            'slug' => 'main-record-school',
            'email' => 'main@example.com',
            'phone' => '+2348000000013',
            'address' => 'Main address',
            'city' => 'Maiduguri',
            'state' => 'Borno',
            'country' => 'Nigeria',
            'is_active' => true,
        ]);

        $schoolAdmin = User::query()->create([
            'name' => 'Main School Admin',
            'email' => 'main-admin@example.com',
            'password' => Hash::make('password'),
            'is_platform_admin' => false,
            'is_active' => true,
        ]);

        $schoolAdmin->schools()->attach($school, [
            'role' => User::SCHOOL_ROLE_ADMIN,
            'is_primary' => true,
        ]);

        $this
            ->actingAs($schoolAdmin)
            ->get($this->portalUrl($school->slug))
            ->assertOk();

        $this
            ->actingAs($schoolAdmin)
            ->get('/admin')
            ->assertRedirect($this->portalUrl($school->slug));
    }

    public function test_admin_registration_is_closed_once_a_user_exists(): void
    {
        User::query()->create([
            'name' => 'Existing Admin',
            'email' => 'existing@example.com',
            'password' => Hash::make('password'),
        ]);

        $this
            ->get('/admin/register')
            ->assertNotFound();
    }

    public function test_admin_registration_is_open_for_first_boot_setup(): void
    {
        $this
            ->get('/admin/register')
            ->assertOk();
    }

    public function test_school_health_page_groups_divisions_under_one_school(): void
    {
        $admin = User::query()->create([
            'name' => 'Health Admin',
            'email' => 'health-admin@example.com',
            'password' => Hash::make('password'),
            'is_platform_admin' => true,
            'is_active' => true,
        ]);

        $school = School::query()->create([
            'name' => 'Green',
            'code' => 'GIS',
            'slug' => 'green',
            'email' => 'green@example.com',
            'phone' => '+2348000000016',
            'address' => 'Green address',
            'city' => 'Maiduguri',
            'state' => 'Borno',
            'country' => 'Nigeria',
            'is_active' => true,
        ]);

        foreach ([
            School::DIVISION_NURSERY => 'Gis-NUR',
            School::DIVISION_PRIMARY => 'Gis-PRI',
            School::DIVISION_SECONDARY => 'Gis-SEC',
        ] as $division => $code) {
            School::query()->create([
                'parent_school_id' => $school->getKey(),
                'division' => $division,
                'name' => 'Green',
                'code' => $code,
                'slug' => str($code)->lower()->toString(),
                'email' => "{$division}@green.example.com",
                'phone' => '+2348000000017',
                'address' => 'Green address',
                'city' => 'Maiduguri',
                'state' => 'Borno',
                'country' => 'Nigeria',
                'is_active' => true,
            ]);
        }

        $response = $this
            ->actingAs($admin)
            ->get('/admin/school-health')
            ->assertOk()
            ->assertSee('Green')
            ->assertSee('Nursery Section')
            ->assertSee('Primary Section')
            ->assertSee('Secondary Section');

        $content = $response->getContent();

        $this->assertStringNotContainsString('Gis-NUR', $content);
        $this->assertStringNotContainsString('Gis-PRI', $content);
        $this->assertStringNotContainsString('Gis-SEC', $content);
    }

    public function test_parent_school_uses_section_logo_when_parent_logo_is_empty(): void
    {
        $school = School::query()->create([
            'name' => 'Logo Parent School',
            'code' => 'LOGO-001',
            'slug' => 'logo-parent-school',
            'email' => 'logo-parent@example.com',
            'phone' => '+2348000000014',
            'address' => 'Logo address',
            'city' => 'Maiduguri',
            'state' => 'Borno',
            'country' => 'Nigeria',
            'is_active' => true,
        ]);

        School::query()->create([
            'parent_school_id' => $school->getKey(),
            'division' => School::DIVISION_PRIMARY,
            'name' => 'Logo Parent School',
            'code' => 'LOGO-PRI',
            'slug' => 'logo-parent-school-primary',
            'logo_path' => 'school-logos/logo.png',
            'email' => 'logo-primary@example.com',
            'phone' => '+2348000000015',
            'address' => 'Logo address',
            'city' => 'Maiduguri',
            'state' => 'Borno',
            'country' => 'Nigeria',
            'is_active' => true,
        ]);

        $this->assertSame('school-logos/logo.png', $school->fresh()->displayLogoPath());
    }

    public function test_school_portal_hides_school_selector_on_tenant_forms(): void
    {
        $this->seed();

        $schoolAdmin = User::query()->where('email', 'principal@demo-school.test')->firstOrFail();
        $tenantSlug = $schoolAdmin->schools()->value('slug');

        $this
            ->actingAs($schoolAdmin)
            ->get($this->portalUrl($tenantSlug, '/academic-years/create'))
            ->assertOk()
            ->assertDontSee('name="data.school_id"', escape: false);

        $this
            ->actingAs($schoolAdmin)
            ->get($this->portalUrl($tenantSlug, '/terms/create'))
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
                'role' => User::SCHOOL_ROLE_TEACHER,
                'is_primary' => false,
            ],
        ]);

        Staff::query()
            ->where('school_id', $school->getKey())
            ->firstOrFail()
            ->update(['user_id' => $teacher->getKey(), 'staff_type' => Staff::TYPE_TEACHING]);

        foreach ([
            $this->portalUrl($tenantSlug, '/my-teaching'),
            $this->portalUrl($tenantSlug, '/class-subjects'),
            $this->portalUrl($tenantSlug, '/student-scores'),
            $this->portalUrl($tenantSlug, '/report-cards'),
        ] as $path) {
            $this
                ->actingAs($teacher)
                ->get($path)
                ->assertOk();
        }

        foreach ([
            $this->portalUrl($tenantSlug, '/staff'),
            $this->portalUrl($tenantSlug, '/students'),
            $this->portalUrl($tenantSlug, '/school-classes'),
            $this->portalUrl($tenantSlug, '/fee-types'),
            $this->portalUrl($tenantSlug, '/student-invoices'),
        ] as $path) {
            $this
                ->actingAs($teacher)
                ->get($path)
                ->assertForbidden();
        }
    }
}
