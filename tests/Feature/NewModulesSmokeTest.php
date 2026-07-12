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
            '/portal/demo-international-school/assignments',
            '/portal/demo-international-school/timetable-entries',
            '/portal/demo-international-school/notices',
            '/portal/demo-international-school/class-timetable',
            '/portal/demo-international-school/fee-debtors',
            '/portal/demo-international-school/student-devices',
            '/portal/demo-international-school/bus-routes',
            '/portal/demo-international-school/student-movements',
            '/portal/demo-international-school/enrollments',
            '/portal/demo-international-school/student-discounts',
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
            '/portal/demo-international-school/assignments',
            '/portal/demo-international-school/timetable-entries',
            '/portal/demo-international-school/notices',
            '/portal/demo-international-school/student-discounts',
            '/portal/demo-international-school/fee-debtors',
            '/portal/demo-international-school/student-invoices',
            '/portal/demo-international-school/fee-payments',
            '/portal/demo-international-school/fee-structures',
        ] as $path) {
            $this
                ->actingAs($admin)
                ->get($path)
                ->assertOk()
                ->assertSeeText('JSS 1');
        }
    }
}
