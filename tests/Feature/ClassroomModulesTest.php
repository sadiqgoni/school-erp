<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentConfirmation;
use App\Models\Enrollment;
use App\Models\Notice;
use App\Models\Student;
use App\Models\TimetableEntry;
use App\Models\User;
use Database\Seeders\ParentAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function parentAndChildPlacement(): array
    {
        $this->seed();
        $this->seed(ParentAccountsSeeder::class);

        $parent = User::query()->where('email', 'guardian@example.com')->firstOrFail();

        $student = Student::query()
            ->whereHas('guardianLinks.guardian', fn ($query) => $query->where('user_id', $parent->getKey()))
            ->firstOrFail();

        $enrollment = Enrollment::query()
            ->where('student_id', $student->getKey())
            ->where('status', 'active')
            ->firstOrFail();

        return [$parent, $student, $enrollment];
    }

    public function test_parent_sees_homework_for_their_childs_class_and_can_confirm_it(): void
    {
        [$parent, $student, $enrollment] = $this->parentAndChildPlacement();

        $assignment = Assignment::query()->create([
            'school_id' => $enrollment->school_id,
            'school_class_id' => $enrollment->school_class_id,
            'class_section_id' => $enrollment->class_section_id,
            'title' => 'Read pages 10 to 15 of the English textbook',
            'assigned_on' => today(),
            'due_on' => today()->addDays(2),
            'status' => Assignment::STATUS_PUBLISHED,
        ]);

        $this
            ->actingAs($parent)
            ->get($this->portalUrl('demo-international-school', '/homework'))
            ->assertOk()
            ->assertSeeText('Read pages 10 to 15');

        AssignmentConfirmation::query()->create([
            'school_id' => $enrollment->school_id,
            'assignment_id' => $assignment->getKey(),
            'student_id' => $student->getKey(),
            'confirmed_by' => $parent->getKey(),
            'confirmed_at' => now(),
        ]);

        $this->assertDatabaseHas('assignment_confirmations', [
            'assignment_id' => $assignment->getKey(),
            'student_id' => $student->getKey(),
        ]);
    }

    public function test_parent_does_not_see_draft_homework(): void
    {
        [$parent, , $enrollment] = $this->parentAndChildPlacement();

        Assignment::query()->create([
            'school_id' => $enrollment->school_id,
            'school_class_id' => $enrollment->school_class_id,
            'title' => 'Secret draft homework',
            'assigned_on' => today(),
            'status' => Assignment::STATUS_DRAFT,
        ]);

        $this
            ->actingAs($parent)
            ->get($this->portalUrl('demo-international-school', '/homework'))
            ->assertOk()
            ->assertDontSeeText('Secret draft homework');
    }

    public function test_parent_sees_their_childs_timetable(): void
    {
        [$parent, , $enrollment] = $this->parentAndChildPlacement();

        TimetableEntry::query()->create([
            'school_id' => $enrollment->school_id,
            'school_class_id' => $enrollment->school_class_id,
            'day_of_week' => 1,
            'period_number' => 1,
            'starts_at' => '08:00',
            'ends_at' => '08:40',
            'label' => 'Morning Mathematics Lesson',
        ]);

        $this
            ->actingAs($parent)
            ->get($this->portalUrl('demo-international-school', '/parent-timetable'))
            ->assertOk()
            ->assertSeeText('Morning Mathematics Lesson');
    }

    public function test_parent_sees_school_wide_notices_but_not_other_division_notices(): void
    {
        [$parent, , $enrollment] = $this->parentAndChildPlacement();

        Notice::query()->create([
            'school_id' => $enrollment->school_id,
            'title' => 'Resumption date announcement',
            'body' => 'School resumes on Monday.',
            'audience_type' => Notice::AUDIENCE_ALL,
            'status' => Notice::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        Notice::query()->create([
            'school_id' => $enrollment->school_id,
            'title' => 'Hidden division-only memo',
            'audience_type' => Notice::AUDIENCE_DIVISION,
            'audience_division' => 'Some Other Division',
            'status' => Notice::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $this
            ->actingAs($parent)
            ->get($this->portalUrl('demo-international-school', '/parent-notices'))
            ->assertOk()
            ->assertSeeText('Resumption date announcement')
            ->assertDontSeeText('Hidden division-only memo');
    }
}
