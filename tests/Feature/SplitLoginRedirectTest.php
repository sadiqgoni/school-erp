<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\SplitLogin;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class SplitLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_logging_in_from_school_portal_goes_to_admin_panel(): void
    {
        School::query()->create([
            'name' => 'Redirect School',
            'code' => 'RED-001',
            'slug' => 'redirect-school',
            'email' => 'redirect@example.com',
            'phone' => '+2348000000015',
            'address' => 'Redirect address',
            'city' => 'Maiduguri',
            'state' => 'Borno',
            'country' => 'Nigeria',
            'is_active' => true,
        ]);

        $admin = User::query()->create([
            'name' => 'Platform Admin',
            'email' => 'platform-login@example.com',
            'password' => Hash::make('password'),
            'is_platform_admin' => true,
            'is_active' => true,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('school'));

        Livewire::test(SplitLogin::class)
            ->set('data.email', $admin->email)
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertRedirect(Filament::getPanel('admin')->getUrl());
    }

    public function test_login_from_a_specific_division_subdomain_lands_there_even_when_its_not_the_default_tenant(): void
    {
        $root = School::query()->create([
            'name' => 'Multi Division School',
            'code' => 'MULTI-001',
            'slug' => 'multi-division-school',
            'email' => 'multi@example.com',
            'country' => 'Nigeria',
            'is_active' => true,
        ]);

        $nursery = School::query()->create([
            'parent_school_id' => $root->getKey(),
            'division' => School::DIVISION_NURSERY,
            'name' => 'Multi Division School',
            'code' => 'MULTI-NUR',
            'slug' => 'multi-division-school-nursery',
            'is_active' => true,
        ]);

        $primary = School::query()->create([
            'parent_school_id' => $root->getKey(),
            'division' => School::DIVISION_PRIMARY,
            'name' => 'Multi Division School',
            'code' => 'MULTI-PRI',
            'slug' => 'multi-division-school-primary',
            'is_active' => true,
        ]);

        $admin = User::query()->create([
            'name' => 'Division Admin',
            'email' => 'division-admin@example.com',
            'password' => Hash::make('password'),
            'is_platform_admin' => false,
            'is_active' => true,
        ]);

        // Primary is flagged as the default tenant - Filament's own login
        // redirect would send the admin here regardless of which division
        // they actually logged into.
        $admin->schools()->attach($nursery->getKey(), ['role' => User::SCHOOL_ROLE_ADMIN, 'is_primary' => false]);
        $admin->schools()->attach($primary->getKey(), ['role' => User::SCHOOL_ROLE_ADMIN, 'is_primary' => true]);

        Filament::setCurrentPanel(Filament::getPanel('school'));

        $this->get($this->portalUrl('multi-division-school-nursery', '/login'));

        Livewire::test(SplitLogin::class)
            ->set('data.email', $admin->email)
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertRedirect($nursery->portalUrl());
    }
}
