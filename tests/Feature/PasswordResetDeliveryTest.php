<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_notification_bypasses_the_queue_even_when_queue_connection_is_database(): void
    {
        // phpunit.xml forces QUEUE_CONNECTION=sync for the whole suite, which
        // masks this exact bug (sync executes immediately either way). Force
        // the real production driver here so this test actually proves the
        // notification bypasses it rather than silently relying on jobs
        // nobody is running to process.
        config(['queue.default' => 'database']);
        Mail::fake();

        $user = User::query()->create([
            'name' => 'Reset Test User',
            'email' => 'reset-test@example.com',
            'password' => bcrypt('old-password'),
            'is_platform_admin' => true,
            'is_active' => true,
        ]);

        $user->sendPasswordResetNotification('a-fake-token');

        $this->assertSame(
            0,
            DB::table('jobs')->count(),
            'A ShouldQueue notification was queued instead of sent immediately — with no queue '
            .'worker running in production this means the email never arrives.',
        );
    }
}
