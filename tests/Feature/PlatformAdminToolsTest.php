<?php

namespace Tests\Feature;

use App\Filament\Widgets\PlatformOverview;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformAdminToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_open_the_new_platform_admin_pages(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        foreach ([
            '/admin',
            '/admin/user-activities',
            '/admin/communication-logs',
            '/admin/school-health',
        ] as $path) {
            $this
                ->actingAs($admin)
                ->get($path)
                ->assertOk();
        }
    }

    public function test_school_admin_cannot_open_platform_admin_only_pages(): void
    {
        $this->seed();

        $schoolAdmin = User::query()->where('email', 'principal@demo-school.test')->firstOrFail();
        $tenantSlug = $schoolAdmin->schools()->value('slug');

        foreach ([
            $this->portalUrl($tenantSlug, '/user-activities'),
            $this->portalUrl($tenantSlug, '/communication-logs'),
            $this->portalUrl($tenantSlug, '/school-health'),
        ] as $path) {
            $this
                ->actingAs($schoolAdmin)
                ->get($path)
                ->assertForbidden();
        }
    }

    public function test_platform_overview_widget_reflects_real_counts(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $expectedSchools = School::query()->count();

        Livewire::test(PlatformOverview::class)
            ->assertSeeText('Schools')
            ->assertSeeText((string) $expectedSchools)
            ->assertSeeText('Students')
            ->assertSeeText('Payments received');
    }

    public function test_school_health_page_flags_a_school_with_no_students(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        School::query()->create([
            'name' => 'Empty Health Check School', 'code' => 'HEALTH-EMPTY', 'slug' => 'empty-health-check-school',
            'division' => School::DIVISION_SECONDARY, 'email' => 'health-empty@example.com',
            'country' => 'Nigeria', 'is_active' => true,
        ]);

        $this
            ->actingAs($admin)
            ->get('/admin/school-health')
            ->assertOk()
            ->assertSeeText('Empty Health Check School');
    }
}
