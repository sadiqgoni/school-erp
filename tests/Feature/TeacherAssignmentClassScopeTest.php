<?php

namespace Tests\Feature;

use App\Filament\Resources\Assignments\Schemas\AssignmentForm;
use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAssignmentClassScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_teacher_only_sees_their_own_classes_on_the_assignment_form(): void
    {
        $this->seed();

        $school = School::query()->firstOrFail();

        $teacher = User::query()->create([
            'name' => 'Probe Teacher',
            'email' => 'probe-teacher@example.com',
            'password' => bcrypt('secret'),
            'is_active' => true,
        ]);
        $teacher->schools()->attach($school, ['role' => User::SCHOOL_ROLE_TEACHER, 'is_primary' => false]);

        $staff = Staff::query()->create([
            'school_id' => $school->getKey(),
            'staff_number' => 'PROBE-001',
            'first_name' => 'Probe',
            'last_name' => 'Teacher',
            'gender' => 'male',
            'staff_type' => Staff::TYPE_TEACHING,
            'status' => 'active',
            'user_id' => $teacher->getKey(),
        ]);

        $ownClass = SchoolClass::query()->where('school_id', $school->getKey())->firstOrFail();
        $otherClass = SchoolClass::query()->create([
            'school_id' => $school->getKey(),
            'name' => 'Probe Other Class',
            'code' => 'PROBE-OTHER',
            'level' => 99,
            'is_active' => true,
        ]);

        $subject = Subject::query()->create([
            'school_id' => $school->getKey(),
            'name' => 'Probe Subject',
            'code' => 'PROBE-SUBJ',
            'is_active' => true,
        ]);

        ClassSubject::query()->create([
            'school_id' => $school->getKey(),
            'school_class_id' => $ownClass->getKey(),
            'subject_id' => $subject->getKey(),
            'staff_id' => $staff->getKey(),
            'is_compulsory' => true,
            'weekly_periods' => 3,
            'is_active' => true,
        ]);

        $this->actingAs($teacher);
        Filament::auth()->login($teacher);
        Filament::setTenant($school);

        $reflection = new \ReflectionClass(AssignmentForm::class);
        $method = $reflection->getMethod('classOptions');
        $method->setAccessible(true);
        $options = $method->invoke(null);

        $this->assertArrayHasKey($ownClass->getKey(), $options, 'Teacher should see their own assigned class.');
        $this->assertArrayNotHasKey(
            $otherClass->getKey(),
            $options,
            'Teacher was able to see a class they are not assigned to teach.',
        );
    }

    public function test_form_teacher_of_one_arm_cannot_assign_homework_to_a_different_arm_of_the_same_class(): void
    {
        $this->seed();

        $school = School::query()->firstOrFail();

        $teacher = User::query()->create([
            'name' => 'Probe Form Teacher',
            'email' => 'probe-form-teacher@example.com',
            'password' => bcrypt('secret'),
            'is_active' => true,
        ]);
        $teacher->schools()->attach($school, ['role' => User::SCHOOL_ROLE_TEACHER, 'is_primary' => false]);

        $staff = Staff::query()->create([
            'school_id' => $school->getKey(),
            'staff_number' => 'PROBE-FORM-001',
            'first_name' => 'Probe',
            'last_name' => 'FormTeacher',
            'gender' => 'female',
            'staff_type' => Staff::TYPE_TEACHING,
            'status' => 'active',
            'user_id' => $teacher->getKey(),
        ]);

        $class = SchoolClass::query()->create([
            'school_id' => $school->getKey(),
            'name' => 'Probe Arms Class',
            'code' => 'PROBE-ARMS',
            'level' => 98,
            'is_active' => true,
        ]);

        $ownArm = ClassSection::query()->create([
            'school_id' => $school->getKey(),
            'school_class_id' => $class->getKey(),
            'name' => 'A',
            'code' => 'PROBE-ARMS-A',
            'is_active' => true,
        ]);

        $otherArm = ClassSection::query()->create([
            'school_id' => $school->getKey(),
            'school_class_id' => $class->getKey(),
            'name' => 'B',
            'code' => 'PROBE-ARMS-B',
            'is_active' => true,
        ]);

        $academicYear = AcademicYear::query()->where('school_id', $school->getKey())->firstOrFail();

        TeachingAssignment::query()->create([
            'school_id' => $school->getKey(),
            'staff_id' => $staff->getKey(),
            'academic_year_id' => $academicYear->getKey(),
            'school_class_id' => $class->getKey(),
            'class_section_id' => $ownArm->getKey(),
            'assignment_role' => TeachingAssignment::ROLE_FORM_TEACHER,
            'is_class_teacher' => true,
            'is_active' => true,
        ]);

        $this->actingAs($teacher);
        Filament::auth()->login($teacher);
        Filament::setTenant($school);

        $reflection = new \ReflectionClass(AssignmentForm::class);
        $method = $reflection->getMethod('sectionOptions');
        $method->setAccessible(true);
        $options = $method->invoke(null, $class->getKey());

        $this->assertArrayHasKey($ownArm->getKey(), $options, 'Form teacher should see their own arm.');
        $this->assertArrayNotHasKey(
            $otherArm->getKey(),
            $options,
            'Form teacher was able to see an arm of the class they are not assigned to.',
        );
    }
}
