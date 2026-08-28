<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewModulesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_module_pages_render_for_school_admin(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        foreach ([
            $this->portalUrl('demo-international-school', '/assignments'),
            $this->portalUrl('demo-international-school', '/timetable-entries'),
            $this->portalUrl('demo-international-school', '/notices'),
            $this->portalUrl('demo-international-school', '/class-timetable'),
            $this->portalUrl('demo-international-school', '/fee-debtors'),
            $this->portalUrl('demo-international-school', '/student-devices'),
            $this->portalUrl('demo-international-school', '/bus-routes'),
            $this->portalUrl('demo-international-school', '/student-movements'),
            $this->portalUrl('demo-international-school', '/enrollments'),
            $this->portalUrl('demo-international-school', '/student-discounts'),
        ] as $path) {
            $this
                ->actingAs($admin)
                ->get($path)
                ->assertOk();
        }
    }

    public function test_class_tabs_render_on_class_based_list_pages(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        foreach ([
            $this->portalUrl('demo-international-school', '/assignments'),
            $this->portalUrl('demo-international-school', '/timetable-entries'),
            $this->portalUrl('demo-international-school', '/notices'),
            $this->portalUrl('demo-international-school', '/student-discounts'),
            $this->portalUrl('demo-international-school', '/fee-debtors'),
            $this->portalUrl('demo-international-school', '/student-invoices'),
            $this->portalUrl('demo-international-school', '/fee-payments'),
            $this->portalUrl('demo-international-school', '/fee-structures'),
        ] as $path) {
            $this
                ->actingAs($admin)
                ->get($path)
                ->assertOk()
                ->assertSeeText('JSS 1');
        }
    }
}
