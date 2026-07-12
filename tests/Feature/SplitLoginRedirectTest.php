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
}
