<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\GuardianStudent;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Database\Seeder;

class NurseryTwoStudentsSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::query()
            ->withoutGlobalScopes()
            ->where(function ($query): void {
                $query->where('division', School::DIVISION_NURSERY)
                    ->orWhere('code', 'like', '%-NUR');
            })
            ->get();

        if ($schools->isEmpty()) {
            $schools = collect([
                School::query()->firstOrCreate(
                    ['code' => 'DEMO-NUR'],
                    [
                        'name' => 'Demo International School Nursery',
                        'slug' => 'demo-international-school-nursery',
                        'division' => School::DIVISION_NURSERY,
                        'email' => 'school@example.com',
                        'phone' => '+2348000000000',
                        'city' => 'Maiduguri',
                        'state' => 'Borno',
                        'country' => 'Nigeria',
                        'primary_color' => '#0f766e',
                        'subscription_plan' => 'trial',
                        'student_limit' => 1000,
                        'enabled_modules' => ['students', 'staff', 'attendance', 'fees', 'exams', 'communications'],
                        'is_active' => true,
                    ],
                ),
            ]);
        }

        foreach ($schools as $school) {
            $this->seedSchool($school);
        }
    }

    protected function seedSchool(School $school): void
    {
        $academicYear = AcademicYear::query()->firstOrCreate(
            [
                'school_id' => $school->getKey(),
                'name' => '2026/2027',
            ],
            [
                'starts_on' => '2026-09-01',
                'ends_on' => '2027-07-31',
                'is_current' => true,
                'is_active' => true,
            ],
        );

        $term = Term::query()
            ->where('school_id', $school->getKey())
            ->where('academic_year_id', $academicYear->getKey())
            ->where('is_current', true)
            ->first()
            ?? Term::query()->firstOrCreate(
                [
                    'school_id' => $school->getKey(),
                    'academic_year_id' => $academicYear->getKey(),
                    'position' => 1,
                ],
                [
                    'name' => 'First Term',
                    'starts_on' => '2026-09-01',
                    'ends_on' => '2026-12-18',
                    'is_current' => true,
                    'is_active' => true,
                ],
            );

        $nurseryTwo = SchoolClass::query()->updateOrCreate(
            [
                'school_id' => $school->getKey(),
                'code' => 'NUR2',
            ],
            [
                'name' => 'Nursery 2',
                'level' => 2,
                'department' => 'Nursery',
                'is_active' => true,
            ],
        );

        $section = ClassSection::query()->updateOrCreate(
            [
                'school_id' => $school->getKey(),
                'school_class_id' => $nurseryTwo->getKey(),
                'code' => 'NUR2-A',
            ],
            [
                'name' => 'A',
                'capacity' => 30,
                'is_active' => true,
            ],
        );

        foreach ($this->students() as $index => $studentData) {
            $studentNumber = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $prefix = $school->code ?: 'NUR2';

            $student = Student::query()->updateOrCreate(
                [
                    'school_id' => $school->getKey(),
                    'admission_number' => "{$prefix}/NUR2/2026/{$studentNumber}",
                ],
                [
                    'first_name' => $studentData['first_name'],
                    'middle_name' => null,
                    'last_name' => $studentData['last_name'],
                    'date_of_birth' => $studentData['date_of_birth'],
                    'gender' => $studentData['gender'],
                    'blood_group' => null,
                    'religion' => null,
                    'phone' => null,
                    'email' => null,
                    'address' => 'Nursery 2 demo address',
                    'city' => 'Maiduguri',
                    'state' => 'Borno',
                    'country' => 'Nigeria',
                    'admitted_on' => '2026-09-01',
                    'status' => 'active',
                    'previous_school' => null,
                    'medical_notes' => null,
                ],
            );

            $guardian = Guardian::query()->updateOrCreate(
                [
                    'school_id' => $school->getKey(),
                    'phone' => '+234809000'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                ],
                [
                    'name' => $studentData['guardian_name'],
                    'alternate_phone' => null,
                    'email' => "nursery.guardian{$studentNumber}@example.com",
                    'occupation' => 'Parent',
                    'address' => 'Nursery 2 guardian address',
                    'is_active' => true,
                ],
            );

            GuardianStudent::query()->updateOrCreate(
                [
                    'guardian_id' => $guardian->getKey(),
                    'student_id' => $student->getKey(),
                ],
                [
                    'school_id' => $school->getKey(),
                    'relationship' => $studentData['relationship'],
                    'is_primary_contact' => true,
                    'can_pick_up' => true,
                    'receives_sms' => true,
                ],
            );

            Enrollment::query()->updateOrCreate(
                [
                    'student_id' => $student->getKey(),
                    'academic_year_id' => $academicYear->getKey(),
                ],
                [
                    'school_id' => $school->getKey(),
                    'term_id' => $term->getKey(),
                    'school_class_id' => $nurseryTwo->getKey(),
                    'class_section_id' => $section->getKey(),
                    'enrolled_on' => '2026-09-01',
                    'status' => 'active',
                    'remarks' => 'Seeded Nursery 2 demo enrollment',
                ],
            );
        }
    }

    protected function students(): array
    {
        return [
            ['first_name' => 'Maryam', 'last_name' => 'Abubakar', 'gender' => 'female', 'date_of_birth' => '2022-02-14', 'guardian_name' => 'Amina Abubakar', 'relationship' => 'mother'],
            ['first_name' => 'David', 'last_name' => 'Okafor', 'gender' => 'male', 'date_of_birth' => '2022-05-03', 'guardian_name' => 'Chinedu Okafor', 'relationship' => 'father'],
            ['first_name' => 'Zainab', 'last_name' => 'Bello', 'gender' => 'female', 'date_of_birth' => '2021-11-21', 'guardian_name' => 'Hauwa Bello', 'relationship' => 'mother'],
            ['first_name' => 'Samuel', 'last_name' => 'James', 'gender' => 'male', 'date_of_birth' => '2022-01-09', 'guardian_name' => 'Grace James', 'relationship' => 'mother'],
            ['first_name' => 'Fatima', 'last_name' => 'Usman', 'gender' => 'female', 'date_of_birth' => '2022-07-18', 'guardian_name' => 'Musa Usman', 'relationship' => 'father'],
        ];
    }
}
