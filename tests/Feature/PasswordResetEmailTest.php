<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use App\Notifications\PasswordResetNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_password_reset_page_sends_immediate_reset_notification(): void
    {
        Notification::fake();

        $user = User::query()->create([
            'name' => 'Reset User',
            'email' => 'reset-user@example.com',
            'password' => Hash::make('old-password'),
            'is_platform_admin' => true,
            'is_active' => true,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(RequestPasswordReset::class)
            ->set('data.email', $user->email)
            ->call('request');

        Notification::assertSentTo($user, PasswordResetNotification::class);
    }

    public function test_password_change_sends_security_alert(): void
    {
        Notification::fake();

        $user = User::query()->create([
            'name' => 'Parent User',
            'email' => 'parent-security@example.com',
            'password' => Hash::make('old-password'),
            'is_platform_admin' => false,
            'is_active' => true,
        ]);

        $user->update(['password' => Hash::make('new-password')]);

        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }
}
