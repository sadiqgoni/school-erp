<?php

namespace Tests\Feature;

use App\Filament\Resources\Students\Pages\ListStudents;
use App\Models\BusRoute;
use App\Models\BusRouteStudent;
use App\Models\Enrollment;
use App\Models\Notice;
use App\Models\ReportCard;
use App\Models\ReportCardTraitRating;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\User;
use App\Support\ExamResultsSampleSetup;
use App\Support\NoticeSampleSetup;
use App\Support\SafetyTransportSampleSetup;
use App\Support\StaffSampleSetup;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class SampleDataGeneratorsTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsSuperAdminOnSchool(School $school): User
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'sample-data-admin@example.com'],
            ['name' => 'Sample Data Admin', 'password' => bcrypt('secret'), 'is_platform_admin' => true, 'is_active' => true],
        );

        $this->actingAs($admin);
        Filament::auth()->login($admin);
        Filament::setTenant($school);

        return $admin;
    }

    protected function createSampleStudents(School $school, int $count): void
    {
        $page = new ListStudents;
        $method = new ReflectionMethod($page, 'createSampleStudents');
        $method->setAccessible(true);
        $method->invoke($page, $count);
    }

    public function test_sample_students_never_repeat_the_same_family_within_one_class(): void
    {
        $school = School::query()->create([
            'name' => 'Sample Primary School', 'code' => 'SAMPLE-PRY', 'slug' => 'sample-primary-school',
            'division' => School::DIVISION_PRIMARY, 'email' => 'sample-pry@example.com', 'country' => 'Nigeria', 'is_active' => true,
        ]);

        $this->actingAsSuperAdminOnSchool($school);
        $this->createSampleStudents($school, 20);

        $familiesPerClass = Enrollment::query()
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->where('enrollments.school_id', $school->getKey())
            ->selectRaw('enrollments.school_class_id, students.last_name, count(*) as occurrences')
            ->groupBy('enrollments.school_class_id', 'students.last_name')
            ->get();

        $repeatedFamilyInSameClass = $familiesPerClass->firstWhere('occurrences', '>', 1);

        $this->assertNull(
            $repeatedFamilyInSameClass,
            'A guardian surname repeated within a single class — the sample-student generator regressed.',
        );

        // The primary division has 6 classes; the old bug made family index
        // and class index move in lockstep, so every class held exactly one
        // family. Confirm each class now has more than one distinct family.
        $distinctFamiliesPerClass = $familiesPerClass
            ->groupBy('school_class_id')
            ->map(fn ($rows) => $rows->pluck('last_name')->unique()->count());

        $this->assertTrue(
            $distinctFamiliesPerClass->every(fn (int $count): bool => $count > 1),
            'Every class should contain students from more than one family.',
        );
    }

    public function test_sample_students_are_fully_nigerian_and_all_enrolled(): void
    {
        $school = School::query()->create([
            'name' => 'Sample Nursery School', 'code' => 'SAMPLE-NUR', 'slug' => 'sample-nursery-school',
            'division' => School::DIVISION_NURSERY, 'email' => 'sample-nur@example.com', 'country' => 'Nigeria', 'is_active' => true,
        ]);

        $this->actingAsSuperAdminOnSchool($school);
        $this->createSampleStudents($school, 12);

        $students = Student::query()->where('school_id', $school->getKey())->get();

        $this->assertCount(12, $students);
        $this->assertTrue($students->every(fn (Student $student): bool => $student->country === 'Nigeria'));
        $this->assertSame(12, Enrollment::query()->where('school_id', $school->getKey())->count());
    }

    public function test_exam_results_sample_setup_scores_students_compiles_and_publishes_report_cards(): void
    {
        $school = School::query()->create([
            'name' => 'Sample Secondary School', 'code' => 'SAMPLE-SEC', 'slug' => 'sample-secondary-school',
            'division' => School::DIVISION_SECONDARY, 'email' => 'sample-sec@example.com', 'country' => 'Nigeria', 'is_active' => true,
        ]);

        $this->actingAsSuperAdminOnSchool($school);

        StaffSampleSetup::createTeachingAssignments($school);
        $this->createSampleStudents($school, 10);

        $result = ExamResultsSampleSetup::createForSchool($school);

        $this->assertGreaterThan(0, $result['students']);
        $this->assertGreaterThan(0, $result['scores']);
        $this->assertSame($result['students'], $result['reportCards']);

        $this->assertSame(
            $result['reportCards'],
            ReportCard::query()->where('school_id', $school->getKey())->where('status', 'published')->count(),
        );

        $this->assertGreaterThan(0, StudentScore::query()->where('school_id', $school->getKey())->count());
        $this->assertGreaterThan(0, ReportCardTraitRating::query()->where('school_id', $school->getKey())->count());

        // Scores should vary — not every student getting an identical score
        // would mean the "randomness" seed collapsed to a constant.
        $distinctScores = StudentScore::query()->where('school_id', $school->getKey())->distinct('score')->count('score');
        $this->assertGreaterThan(1, $distinctScores);
    }

    public function test_exam_results_sample_setup_is_a_no_op_without_students(): void
    {
        $school = School::query()->create([
            'name' => 'Empty School', 'code' => 'SAMPLE-EMPTY', 'slug' => 'empty-school',
            'division' => School::DIVISION_SECONDARY, 'email' => 'empty@example.com', 'country' => 'Nigeria', 'is_active' => true,
        ]);

        $result = ExamResultsSampleSetup::createForSchool($school);

        $this->assertSame(['students' => 0, 'scores' => 0, 'reportCards' => 0], $result);
    }

    public function test_notice_sample_setup_creates_published_notices(): void
    {
        $school = School::query()->create([
            'name' => 'Notice Sample School', 'code' => 'SAMPLE-NOTICE', 'slug' => 'notice-sample-school',
            'division' => School::DIVISION_SECONDARY, 'email' => 'notice-sample@example.com', 'country' => 'Nigeria', 'is_active' => true,
        ]);

        $count = NoticeSampleSetup::createForSchool($school);

        $this->assertGreaterThan(3, $count);
        $this->assertSame(
            $count,
            Notice::query()->where('school_id', $school->getKey())->where('status', Notice::STATUS_PUBLISHED)->count(),
        );
    }

    public function test_transport_sample_setup_uses_kano_routes_and_pickup_points(): void
    {
        $school = School::query()->create([
            'name' => 'Transport Sample School', 'code' => 'SAMPLE-TRANS', 'slug' => 'transport-sample-school',
            'division' => School::DIVISION_SECONDARY, 'email' => 'transport-sample@example.com', 'country' => 'Nigeria', 'is_active' => true,
        ]);

        $this->actingAsSuperAdminOnSchool($school);
        $this->createSampleStudents($school, 8);

        $result = SafetyTransportSampleSetup::createForSchool($school);

        $this->assertGreaterThan(0, $result['routes']);
        $this->assertGreaterThan(0, $result['assignments']);

        $this->assertDatabaseHas('bus_routes', [
            'school_id' => $school->getKey(),
            'name' => 'Zoo Road Route',
        ]);

        $this->assertDatabaseHas('bus_routes', [
            'school_id' => $school->getKey(),
            'name' => 'Bompai Route',
        ]);

        $this->assertTrue(
            BusRoute::query()
                ->where('school_id', $school->getKey())
                ->where('plate_number', 'like', 'KNK-%')
                ->exists(),
        );

        $this->assertTrue(
            BusRouteStudent::query()
                ->where('school_id', $school->getKey())
                ->where('pickup_point', 'like', '%, Kano')
                ->exists(),
        );
    }
}
