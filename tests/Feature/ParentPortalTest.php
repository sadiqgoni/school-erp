<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ParentAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_view_parent_portal_but_not_school_resources(): void
    {
        $this->seed();
        $this->seed(ParentAccountsSeeder::class);

        $parent = User::query()
            ->where('email', 'guardian@example.com')
            ->firstOrFail();

        $this
            ->actingAs($parent)
            ->get('/portal/demo-international-school/parent-portal')
            ->assertOk()
            ->assertSee('Aisha Musa')
            ->assertSeeText('My Invoices')
            ->assertSeeText('My Results')
            ->assertDontSee('Score Entry');

        $this
            ->actingAs($parent)
            ->get('/portal/demo-international-school')
            ->assertOk()
            ->assertSeeText('Parent Workspace')
            ->assertDontSeeText('Admit Student')
            ->assertDontSeeText('Generate Invoice');

        $this
            ->actingAs($parent)
            ->get('/portal/demo-international-school/parent-invoices')
            ->assertOk()
            ->assertSeeText('Aisha Musa')
            ->assertSeeText('INV-');

        $this
            ->actingAs($parent)
            ->get('/portal/demo-international-school/parent-report-cards')
            ->assertOk()
            ->assertSeeText('My Results');

        $this
            ->actingAs($parent)
            ->get('/portal/demo-international-school/students')
            ->assertForbidden();
    }
}
