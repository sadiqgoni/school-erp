<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivated_school_blocks_its_own_admin(): void
    {
        $this->seed();

        $schoolAdmin = User::query()->where('email', 'principal@demo-school.test')->firstOrFail();
        $school = $schoolAdmin->schools()->firstOrFail();

        $school->forceFill(['is_active' => false])->saveQuietly();

        $this
            ->actingAs($schoolAdmin)
            ->get($this->portalUrl($school->slug))
            ->assertForbidden();
    }

    public function test_expired_subscription_blocks_school_admin(): void
    {
        $this->seed();

        $schoolAdmin = User::query()->where('email', 'principal@demo-school.test')->firstOrFail();
        $school = $schoolAdmin->schools()->firstOrFail();

        $school->forceFill(['subscription_expires_at' => now()->subDay()])->saveQuietly();

        $this
            ->actingAs($schoolAdmin)
            ->get($this->portalUrl($school->slug))
            ->assertForbidden();
    }

    public function test_superadmin_can_still_open_a_deactivated_or_expired_school(): void
    {
        $this->seed();

        $superadmin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $school = School::query()->create([
            'name' => 'Lapsed Academy',
            'code' => 'LAPSED-001',
            'slug' => 'lapsed-academy',
            'email' => 'lapsed@example.com',
            'country' => 'Nigeria',
            'is_active' => false,
            'subscription_expires_at' => now()->subMonth(),
        ]);

        $this
            ->actingAs($superadmin)
            ->get($this->portalUrl($school->slug))
            ->assertOk();
    }

    public function test_active_subscription_is_unaffected(): void
    {
        $this->seed();

        $schoolAdmin = User::query()->where('email', 'principal@demo-school.test')->firstOrFail();
        $school = $schoolAdmin->schools()->firstOrFail();

        $school->forceFill(['subscription_expires_at' => now()->addYear()])->saveQuietly();

        $this
            ->actingAs($schoolAdmin)
            ->get($this->portalUrl($school->slug))
            ->assertOk();
    }
}
