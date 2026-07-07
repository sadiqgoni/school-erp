<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\StudentDevice;
use App\Models\StudentMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceEventTest extends TestCase
{
    use RefreshDatabase;

    protected function deviceSetup(): array
    {
        $this->seed();

        StudentMovement::query()->delete();
        StudentDevice::query()->delete();

        $student = Student::query()->firstOrFail();

        $school = School::query()->withoutGlobalScopes()->findOrFail($student->school_id);
        $school->forceFill(['device_api_token' => 'test-gateway-token'])->save();

        $device = StudentDevice::query()->create([
            'school_id' => $school->getKey(),
            'student_id' => $student->getKey(),
            'identifier' => 'NFC-CARD-001',
            'device_type' => 'nfc_card',
            'is_active' => true,
        ]);

        return [$school, $student, $device];
    }

    public function test_scan_with_valid_token_records_arrival_then_toggles_to_exit(): void
    {
        [, $student] = $this->deviceSetup();

        $this
            ->postJson('/devices/events', [
                'token' => 'test-gateway-token',
                'device' => 'NFC-CARD-001',
            ])
            ->assertOk()
            ->assertJsonPath('event', 'Arrived school');

        $this
            ->postJson('/devices/events', [
                'token' => 'test-gateway-token',
                'device' => 'NFC-CARD-001',
            ])
            ->assertOk()
            ->assertJsonPath('event', 'Left school');

        $this->assertSame(2, StudentMovement::query()->where('student_id', $student->getKey())->count());
    }

    public function test_scan_with_invalid_token_is_rejected(): void
    {
        $this->deviceSetup();

        $this
            ->postJson('/devices/events', [
                'token' => 'wrong-token',
                'device' => 'NFC-CARD-001',
            ])
            ->assertUnauthorized();

        $this->assertSame(0, StudentMovement::query()->count());
    }

    public function test_scan_from_unknown_device_is_rejected(): void
    {
        $this->deviceSetup();

        $this
            ->postJson('/devices/events', [
                'token' => 'test-gateway-token',
                'device' => 'NO-SUCH-CARD',
            ])
            ->assertNotFound();
    }
}
