<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\UserActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailVolumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_high_volume_models_no_longer_generate_audit_rows(): void
    {
        $this->seed();

        $school = School::query()->firstOrFail();

        $studentsBefore = UserActivity::query()->where('auditable_type', Student::class)->count();

        // Enrollment and StudentScore churn far faster than anything else in
        // the app (a single sample-results click can create hundreds), and
        // were deliberately dropped from the audited model list.
        $enrollmentActivityBefore = UserActivity::query()->where('auditable_type', Enrollment::class)->count();
        $scoreActivityBefore = UserActivity::query()->where('auditable_type', StudentScore::class)->count();

        $this->assertSame(0, $enrollmentActivityBefore);
        $this->assertSame(0, $scoreActivityBefore);
        $this->assertSame(0, $studentsBefore);
    }

    public function test_old_activity_rows_are_prunable(): void
    {
        // created_at is not mass-fillable, so set it via forceFill — an
        // already-dirty timestamp attribute survives Eloquent's automatic
        // "stamp with now()" behavior on save.
        $activity = UserActivity::query()->create(['action' => 'created', 'description' => 'Old test row']);
        $activity->forceFill(['created_at' => now()->subDays(UserActivity::RETENTION_DAYS + 1)])->saveQuietly();

        $recent = UserActivity::query()->create(['action' => 'created', 'description' => 'Recent test row']);
        $recent->forceFill(['created_at' => now()->subDay()])->saveQuietly();

        $this->artisan('model:prune', ['--model' => UserActivity::class])->run();

        $this->assertModelMissing($activity);
        $this->assertModelExists($recent);
    }
}
