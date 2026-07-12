<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_pages_are_disabled(): void
    {
        $admin = User::query()->create([
            'name' => 'Fresh Admin',
            'email' => 'fresh-admin@example.com',
            'password' => Hash::make('temporary-pass'),
            'is_platform_admin' => true,
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $this
            ->actingAs($admin)
            ->get('/admin')
            ->assertOk();

        $this
            ->actingAs($admin)
            ->get('/admin/profile')
            ->assertNotFound();
    }

    public function test_flagged_school_user_is_not_redirected_to_profile(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $admin->forceFill(['must_change_password' => true])->save();

        $this
            ->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_changing_password_clears_the_flag(): void
    {
        $user = User::query()->create([
            'name' => 'Changer',
            'email' => 'changer@example.com',
            'password' => Hash::make('old-temp-pass'),
            'is_platform_admin' => true,
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $user->update(['password' => Hash::make('a-brand-new-pass')]);

        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_unflagged_user_is_not_redirected(): void
    {
        $admin = User::query()->create([
            'name' => 'Normal Admin',
            'email' => 'normal-admin@example.com',
            'password' => Hash::make('secret-pass'),
            'is_platform_admin' => true,
            'is_active' => true,
        ]);

        $this
            ->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }
}
