<?php

namespace Tests\Feature;

use App\Models\ClassSection;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\TimetableEntry;
use App\Models\User;
use App\Support\StaffSampleSetup;
use App\Support\TimetableSampleSetup;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableSampleSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsSuperAdminOnSchool(School $school): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'timetable-sample-admin@example.com'],
            ['name' => 'Timetable Sample Admin', 'password' => bcrypt('secret'), 'is_platform_admin' => true, 'is_active' => true],
        );

        $this->actingAs($admin);
        Filament::auth()->login($admin);
        Filament::setTenant($school);
    }

    public function test_sample_timetable_uses_the_full_curriculum_not_just_two_subjects(): void
    {
        $school = School::query()->create([
            'name' => 'Timetable Variety School', 'code' => 'TT-VARIETY', 'slug' => 'timetable-variety-school',
            'division' => School::DIVISION_PRIMARY, 'email' => 'tt-variety@example.com', 'country' => 'Nigeria', 'is_active' => true,
        ]);

        $this->actingAsSuperAdminOnSchool($school);

        StaffSampleSetup::createTeachingAssignments($school);
        TimetableSampleSetup::createForSchool($school);

        $class = SchoolClass::query()->where('school_id', $school->getKey())->orderBy('level')->firstOrFail();

        $distinctSubjects = TimetableEntry::query()
            ->where('school_class_id', $class->getKey())
            ->where('entry_type', 'lesson')
            ->distinct('subject_id')
            ->count('subject_id');

        $this->assertGreaterThan(
            2,
            $distinctSubjects,
            'The generated timetable should draw from the whole curriculum, not cluster into just two subjects.',
        );
    }

    public function test_two_arms_of_the_same_class_do_not_get_an_identical_timetable(): void
    {
        $school = School::query()->create([
            'name' => 'Timetable Arms School', 'code' => 'TT-ARMS', 'slug' => 'timetable-arms-school',
            'division' => School::DIVISION_PRIMARY, 'email' => 'tt-arms@example.com', 'country' => 'Nigeria', 'is_active' => true,
        ]);

        $this->actingAsSuperAdminOnSchool($school);

        StaffSampleSetup::createTeachingAssignments($school);

        $class = SchoolClass::query()->where('school_id', $school->getKey())->orderBy('level')->firstOrFail();

        $armA = ClassSection::query()->updateOrCreate(
            ['school_id' => $school->getKey(), 'school_class_id' => $class->getKey(), 'code' => $class->code.'-A'],
            ['name' => 'A', 'capacity' => 35, 'is_active' => true],
        );
        $armB = ClassSection::query()->create(
            ['school_id' => $school->getKey(), 'school_class_id' => $class->getKey(), 'code' => $class->code.'-B', 'name' => 'B', 'capacity' => 35, 'is_active' => true],
        );

        TimetableSampleSetup::createForSchool($school);

        $sequenceFor = fn (ClassSection $section) => TimetableEntry::query()
            ->where('school_class_id', $class->getKey())
            ->where('class_section_id', $section->getKey())
            ->where('entry_type', 'lesson')
            ->orderBy('day_of_week')
            ->orderBy('period_number')
            ->pluck('subject_id')
            ->all();

        $this->assertNotSame(
            $sequenceFor($armA),
            $sequenceFor($armB),
            'Arm A and Arm B of the same class ended up with an identical weekly timetable.',
        );
    }

    public function test_sample_timetable_is_reproducible_on_repeat_runs(): void
    {
        $school = School::query()->create([
            'name' => 'Timetable Idempotent School', 'code' => 'TT-IDEMPOTENT', 'slug' => 'timetable-idempotent-school',
            'division' => School::DIVISION_SECONDARY, 'email' => 'tt-idempotent@example.com', 'country' => 'Nigeria', 'is_active' => true,
        ]);

        $this->actingAsSuperAdminOnSchool($school);

        StaffSampleSetup::createTeachingAssignments($school);
        TimetableSampleSetup::createForSchool($school);

        $class = SchoolClass::query()->where('school_id', $school->getKey())->orderBy('level')->firstOrFail();

        $before = TimetableEntry::query()
            ->where('school_class_id', $class->getKey())
            ->orderBy('day_of_week')->orderBy('period_number')
            ->pluck('subject_id')
            ->all();

        TimetableSampleSetup::createForSchool($school);

        $after = TimetableEntry::query()
            ->where('school_class_id', $class->getKey())
            ->orderBy('day_of_week')->orderBy('period_number')
            ->pluck('subject_id')
            ->all();

        $this->assertSame($before, $after);
    }
}
