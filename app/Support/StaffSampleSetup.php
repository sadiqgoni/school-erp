<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\Department;
use App\Models\PayrollItemType;
use App\Models\PayrollSheet;
use App\Models\SalaryTemplate;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\StaffBank;
use App\Models\StaffRole;
use App\Models\StaffRoleAssignment;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StaffSampleSetup
{
    public static function createDepartments(School $school): int
    {
        return collect(self::departments($school))->map(fn (array $department) => Department::query()->updateOrCreate(
            [
                'school_id' => $school->getKey(),
                'code' => $department['code'],
            ],
            [
                'name' => $department['name'],
                'description' => $department['description'],
                'is_active' => true,
            ],
        ))->count();
    }

    public static function createStaffRoles(School $school): int
    {
        return collect(self::roles($school))->map(fn (array $role) => StaffRole::query()->updateOrCreate(
            [
                'school_id' => $school->getKey(),
                'code' => $role['code'],
            ],
            [
                'name' => $role['name'],
                'description' => $role['description'],
                'permissions' => [],
                'is_active' => true,
            ],
        ))->count();
    }

    public static function createStaff(School $school): int
    {
        self::createDepartments($school);
        self::createStaffRoles($school);

        $departments = Department::query()
            ->where('school_id', $school->getKey())
            ->get()
            ->keyBy('code');

        $roles = StaffRole::query()
            ->where('school_id', $school->getKey())
            ->get()
            ->keyBy('code');
        $banks = self::ensureStaffBanks($school);
        $salaryTemplates = self::ensureSalaryTemplates($school);
        $payrollSheets = self::ensurePayrollSheets($school);
        self::ensureSalaryItems($school);

        return collect(self::staffProfiles($school))->map(function (array $profile, int $index) use ($school, $departments, $roles, $banks, $salaryTemplates, $payrollSheets): Staff {
            $staffBank = $banks->values()->get($index % max($banks->count(), 1));
            $salaryTemplate = $salaryTemplates->first(fn (SalaryTemplate $template): bool => $template->code === ($profile['salary_scale_code'] ?? null))
                ?? $salaryTemplates->first(fn (SalaryTemplate $template): bool => (float) $template->monthly_basic >= (float) ($profile['salary'] ?? 0))
                ?? $salaryTemplates->last();
            $payrollSheet = $payrollSheets->get($profile['payroll_sheet'] ?? 'General');

            $staff = Staff::query()->updateOrCreate(
                [
                    'school_id' => $school->getKey(),
                    'staff_number' => self::staffNumber($school, $index + 1),
                ],
                [
                    'department_id' => $departments->get($profile['department_code'])?->getKey(),
                    'staff_type' => $profile['staff_type'],
                    'first_name' => $profile['first_name'],
                    'middle_name' => $profile['middle_name'] ?? null,
                    'last_name' => $profile['last_name'],
                    'gender' => $profile['gender'],
                    'date_of_birth' => $profile['date_of_birth'],
                    'phone' => $profile['phone'],
                    'email' => $profile['email'],
                    'address' => $profile['address'],
                    'city' => $profile['city'],
                    'state' => $profile['state'],
                    'country' => 'Nigeria',
                    'employment_type' => 'full_time',
                    'job_title' => $profile['job_title'],
                    'highest_qualification' => $profile['qualification'],
                    'course_specialization' => $profile['specialization'] ?? null,
                    'education_school' => $profile['education_school'] ?? null,
                    'trcn_number' => $profile['trcn_number'] ?? null,
                    'hire_date' => now()->subYears(2)->startOfYear()->toDateString(),
                    'basic_salary' => $salaryTemplate?->monthly_basic ?? $profile['salary'],
                    'salary_template_id' => $salaryTemplate?->getKey(),
                    'salary_grade_level' => $salaryTemplate?->grade_level,
                    'salary_step' => $salaryTemplate?->step,
                    'payroll_sheet_id' => $payrollSheet?->getKey(),
                    'staff_bank_id' => $staffBank?->getKey(),
                    'bank_name' => $staffBank?->name,
                    'bank_account_name' => "{$profile['first_name']} {$profile['last_name']}",
                    'bank_account_number' => '20'.str_pad((string) ($index + 1), 8, '0', STR_PAD_LEFT),
                    'status' => 'active',
                    'next_of_kin_name' => $profile['next_of_kin'],
                    'next_of_kin_relation' => 'Spouse',
                    'next_of_kin_phone' => $profile['next_of_kin_phone'],
                    'notes' => null,
                ],
            );

            if ($role = $roles->get($profile['role_code'])) {
                StaffRoleAssignment::query()->updateOrCreate(
                    [
                        'staff_id' => $staff->getKey(),
                        'staff_role_id' => $role->getKey(),
                    ],
                    [
                        'school_id' => $school->getKey(),
                        'assigned_on' => now()->subYears(2)->startOfYear()->toDateString(),
                        'is_primary' => true,
                        'is_active' => true,
                    ],
                );
            }

            return $staff;
        })->count();
    }

    public static function createTeachingAssignments(School $school): array
    {
        return DB::transaction(function () use ($school): array {
            self::createStaff($school);

            [$academicYear, $term] = self::ensureCurrentSession($school);
            $classes = self::ensureClasses($school);
            $sections = self::ensureSections($school, $classes);
            $subjects = self::ensureSubjects($school);
            $teachers = Staff::query()
                ->where('school_id', $school->getKey())
                ->where('staff_type', Staff::TYPE_TEACHING)
                ->orderBy('id')
                ->get();

            if ($teachers->isEmpty() || $classes->isEmpty()) {
                return ['form' => 0, 'subjects' => 0];
            }

            $formAssignments = 0;
            $subjectAssignments = 0;

            foreach ($classes->values() as $classIndex => $class) {
                $teacher = $teachers[$classIndex % $teachers->count()];
                $section = $sections[$class->getKey()] ?? null;

                TeachingAssignment::query()->updateOrCreate(
                    [
                        'staff_id' => $teacher->getKey(),
                        'academic_year_id' => $academicYear->getKey(),
                        'school_class_id' => $class->getKey(),
                        'class_section_id' => $section?->getKey(),
                        'assignment_role' => TeachingAssignment::ROLE_FORM_TEACHER,
                    ],
                    [
                        'school_id' => $school->getKey(),
                        'term_id' => $term->getKey(),
                        'subject_id' => null,
                        'is_class_teacher' => true,
                        'is_active' => true,
                    ],
                );

                $formAssignments++;

                foreach ($subjects->take(self::subjectsPerClass($school)) as $subjectIndex => $subject) {
                    $subjectTeacher = $teachers[($classIndex + $subjectIndex) % $teachers->count()];

                    ClassSubject::query()->updateOrCreate(
                        [
                            'school_class_id' => $class->getKey(),
                            'subject_id' => $subject->getKey(),
                        ],
                        [
                            'school_id' => $school->getKey(),
                            'staff_id' => $subjectTeacher->getKey(),
                            'teacher_id' => $subjectTeacher->user_id,
                            'weekly_periods' => self::weeklyPeriods($subject->code),
                            'is_compulsory' => true,
                            'is_active' => true,
                        ],
                    );

                    TeachingAssignment::query()->updateOrCreate(
                        [
                            'staff_id' => $subjectTeacher->getKey(),
                            'academic_year_id' => $academicYear->getKey(),
                            'school_class_id' => $class->getKey(),
                            'class_section_id' => $section?->getKey(),
                            'subject_id' => $subject->getKey(),
                        ],
                        [
                            'school_id' => $school->getKey(),
                            'term_id' => $term->getKey(),
                            'assignment_role' => TeachingAssignment::ROLE_SUBJECT_TEACHER,
                            'is_class_teacher' => false,
                            'is_active' => true,
                        ],
                    );

                    $subjectAssignments++;
                }
            }

            return ['form' => $formAssignments, 'subjects' => $subjectAssignments];
        });
    }

    protected static function departments(School $school): array
    {
        return match ($school->division) {
            'nursery' => [
                ['code' => 'NURSERY', 'name' => 'Nursery Academics', 'description' => 'Early years teaching, care, and classroom supervision.'],
                ['code' => 'ADMIN', 'name' => 'Administration', 'description' => 'Front desk, records, and parent enquiries.'],
                ['code' => 'ACCOUNTS', 'name' => 'Accounts', 'description' => 'Fees, receipts, petty cash, and finance records.'],
                ['code' => 'HEALTH', 'name' => 'Health & Welfare', 'description' => 'Basic first aid, hygiene, and child welfare.'],
            ],
            'primary' => [
                ['code' => 'PRIMARY', 'name' => 'Primary Academics', 'description' => 'Class teaching, lesson delivery, and academic monitoring.'],
                ['code' => 'ADMIN', 'name' => 'Administration', 'description' => 'Records, admissions support, and office work.'],
                ['code' => 'ACCOUNTS', 'name' => 'Accounts', 'description' => 'Fees, receipts, payroll support, and finance records.'],
                ['code' => 'ICT', 'name' => 'ICT', 'description' => 'Computer studies, devices, and basic technology support.'],
            ],
            default => [
                ['code' => 'SCIENCE', 'name' => 'Science Department', 'description' => 'Science subjects and laboratory coordination.'],
                ['code' => 'ARTS', 'name' => 'Arts & Humanities', 'description' => 'Languages, arts, social science, and humanities.'],
                ['code' => 'COMMERCIAL', 'name' => 'Commercial Department', 'description' => 'Business, accounting, economics, and commerce.'],
                ['code' => 'ADMIN', 'name' => 'Administration', 'description' => 'Records, discipline support, and general office work.'],
                ['code' => 'ACCOUNTS', 'name' => 'Accounts', 'description' => 'Fees, receipts, payroll support, and finance records.'],
            ],
        };
    }

    protected static function roles(School $school): array
    {
        return match ($school->division) {
            'nursery' => [
                ['code' => 'DIRECTOR', 'name' => 'Director / Proprietor', 'description' => 'Provides ownership oversight and school direction.'],
                ['code' => 'HEAD-NURSERY', 'name' => 'Head of Nursery', 'description' => 'Leads the nursery section and supervises early years staff.'],
                ['code' => 'NURSERY-TEACHER', 'name' => 'Nursery Teacher', 'description' => 'Handles nursery learning activities and classroom routines.'],
                ['code' => 'ASSISTANT-TEACHER', 'name' => 'Assistant Teacher', 'description' => 'Supports class care, activities, and pupil supervision.'],
                ['code' => 'BURSAR', 'name' => 'Bursar', 'description' => 'Handles fee collection and school finance records.'],
            ],
            'primary' => [
                ['code' => 'DIRECTOR', 'name' => 'Director / Proprietor', 'description' => 'Provides ownership oversight and school direction.'],
                ['code' => 'HEAD-TEACHER', 'name' => 'Head Teacher', 'description' => 'Leads the primary section and supervises teaching.'],
                ['code' => 'ASSISTANT-HEAD', 'name' => 'Assistant Head Teacher', 'description' => 'Supports academics, discipline, and teacher coordination.'],
                ['code' => 'CLASS-TEACHER', 'name' => 'Class Teacher', 'description' => 'Manages a class and delivers core lessons.'],
                ['code' => 'BURSAR', 'name' => 'Bursar', 'description' => 'Handles fee collection and school finance records.'],
                ['code' => 'REGISTRAR', 'name' => 'Registrar', 'description' => 'Keeps admissions, records, and school documentation.'],
            ],
            default => [
                ['code' => 'DIRECTOR', 'name' => 'Director / Proprietor', 'description' => 'Provides ownership oversight and school direction.'],
                ['code' => 'PRINCIPAL', 'name' => 'Principal', 'description' => 'Leads school administration and academic operations.'],
                ['code' => 'VICE-PRINCIPAL-ACADEMIC', 'name' => 'Vice Principal Academics', 'description' => 'Coordinates academics, curriculum, and examinations.'],
                ['code' => 'VICE-PRINCIPAL-ADMIN', 'name' => 'Vice Principal Administration', 'description' => 'Supports discipline, operations, and staff coordination.'],
                ['code' => 'HOD', 'name' => 'Head of Department', 'description' => 'Coordinates teachers and subjects within a department.'],
                ['code' => 'SUBJECT-TEACHER', 'name' => 'Subject Teacher', 'description' => 'Teaches assigned subjects and classes.'],
                ['code' => 'BURSAR', 'name' => 'Bursar', 'description' => 'Handles fee collection and school finance records.'],
                ['code' => 'REGISTRAR', 'name' => 'Registrar', 'description' => 'Keeps admissions, records, and school documentation.'],
            ],
        };
    }

    protected static function ensureStaffBanks(School $school): Collection
    {
        return collect(['Access Bank', 'First Bank', 'GTBank', 'UBA', 'Zenith Bank', 'Jaiz Bank'])
            ->map(fn (string $name): StaffBank => StaffBank::query()->updateOrCreate(
                ['school_id' => $school->getKey(), 'name' => $name],
                ['is_active' => true, 'notes' => 'Sample staff salary bank.'],
            ));
    }

    protected static function ensurePayrollSheets(School $school): Collection
    {
        return collect([
            ['name' => 'Management', 'description' => 'Leadership and executive payroll sheet.'],
            ['name' => 'Teaching Sheet', 'description' => 'Teaching staff monthly payroll sheet.'],
            ['name' => 'Operations', 'description' => 'Admin, bursary, registry, and support payroll sheet.'],
        ])->mapWithKeys(fn (array $sheet): array => [
            $sheet['name'] => PayrollSheet::query()->updateOrCreate(
                ['school_id' => $school->getKey(), 'name' => $sheet['name']],
                ['description' => $sheet['description'], 'is_active' => true],
            ),
        ]);
    }

    protected static function ensureSalaryTemplates(School $school): Collection
    {
        return collect(self::salaryScaleMatrix())
            ->flatMap(function (array $steps, string $grade): Collection {
                return collect($steps)->map(function (float $monthlyBasic, int $stepNumber) use ($grade): array {
                    $normalizedGrade = str_pad($grade, 2, '0', STR_PAD_LEFT);
                    $step = str_pad((string) ($stepNumber + 1), 2, '0', STR_PAD_LEFT);

                    return [
                        'code' => "GL{$normalizedGrade}-S{$step}",
                        'name' => "Grade Level {$normalizedGrade} Step {$step}",
                        'grade_level' => "GL {$normalizedGrade}",
                        'step' => $step,
                        'monthly_basic' => $monthlyBasic,
                        'annual_basic' => round($monthlyBasic * 12, 2),
                    ];
                });
            })
            ->map(fn (array $template): SalaryTemplate => SalaryTemplate::query()->updateOrCreate(
                ['school_id' => $school->getKey(), 'code' => $template['code']],
                $template + [
                    'housing_allowance' => 0,
                    'transport_allowance' => 0,
                    'meal_allowance' => 0,
                    'other_allowance' => 0,
                    'pension_deduction' => 0,
                    'tax_deduction' => 0,
                    'other_deduction' => 0,
                    'is_active' => true,
                    'notes' => 'Sample salary scale for testing.',
                ],
            ))->sortBy('monthly_basic')->values();
    }

    protected static function ensureSalaryItems(School $school): void
    {
        FinanceSampleSetup::createPayrollElements($school);

        PayrollItemType::query()
            ->where('school_id', $school->getKey())
            ->update([
                'salary_template_id' => null,
                'grade_level' => null,
                'step' => null,
            ]);
    }

    protected static function staffProfiles(School $school): array
    {
        $base = [
            ['first_name' => 'Ibrahim', 'middle_name' => 'Sani', 'last_name' => 'Abubakar', 'gender' => 'male', 'role_code' => 'DIRECTOR', 'department_code' => 'ADMIN', 'staff_type' => Staff::TYPE_NON_TEACHING, 'job_title' => 'Director / Proprietor', 'qualification' => 'm_ed', 'specialization' => 'Educational Administration', 'email' => 'director.'.Str::slug($school->slug).'@example.com', 'phone' => '+2348022200011', 'salary_scale_code' => 'GL08-S10'],
            ['first_name' => 'Hadiza', 'middle_name' => 'Bilkisu', 'last_name' => 'Garba', 'gender' => 'female', 'role_code' => 'BURSAR', 'department_code' => 'ACCOUNTS', 'staff_type' => Staff::TYPE_NON_TEACHING, 'job_title' => 'Bursar', 'qualification' => 'hnd', 'specialization' => 'Accounting', 'email' => 'bursar.'.Str::slug($school->slug).'@example.com', 'phone' => '+2348022200012', 'salary_scale_code' => 'GL06-S08'],
        ];

        $section = match ($school->division) {
            'nursery' => [
                ['first_name' => 'Amina', 'middle_name' => 'Rahma', 'last_name' => 'Bello', 'gender' => 'female', 'role_code' => 'HEAD-NURSERY', 'department_code' => 'NURSERY', 'staff_type' => Staff::TYPE_TEACHING, 'job_title' => 'Head of Nursery', 'qualification' => 'nce', 'specialization' => 'Early Childhood Education', 'email' => 'head.nursery.'.Str::slug($school->slug).'@example.com', 'phone' => '+2348022200013', 'salary_scale_code' => 'GL07-S04'],
                ['first_name' => 'Maryam', 'middle_name' => null, 'last_name' => 'Lawal', 'gender' => 'female', 'role_code' => 'NURSERY-TEACHER', 'department_code' => 'NURSERY', 'staff_type' => Staff::TYPE_TEACHING, 'job_title' => 'Nursery Teacher', 'qualification' => 'nce', 'specialization' => 'Early Childhood Education', 'email' => 'maryam.lawal.'.Str::slug($school->slug).'@example.com', 'phone' => '+2348022200014', 'salary_scale_code' => 'GL05-S04'],
                ['first_name' => 'Comfort', 'middle_name' => 'Bot', 'last_name' => 'Gyang', 'gender' => 'female', 'role_code' => 'ASSISTANT-TEACHER', 'department_code' => 'NURSERY', 'staff_type' => Staff::TYPE_TEACHING, 'job_title' => 'Assistant Teacher', 'qualification' => 'nce', 'specialization' => 'Child Care', 'email' => 'comfort.gyang.'.Str::slug($school->slug).'@example.com', 'phone' => '+2348022200015', 'salary_scale_code' => 'GL03-S06'],
            ],
            'primary' => [
                ['first_name' => 'Samuel', 'middle_name' => 'Tersoo', 'last_name' => 'Audu', 'gender' => 'male', 'role_code' => 'HEAD-TEACHER', 'department_code' => 'PRIMARY', 'staff_type' => Staff::TYPE_TEACHING, 'job_title' => 'Head Teacher', 'qualification' => 'b_ed', 'specialization' => 'Primary Education', 'email' => 'head.primary.'.Str::slug($school->slug).'@example.com', 'phone' => '+2348022200013', 'salary_scale_code' => 'GL07-S08'],
                ['first_name' => 'Zainab', 'middle_name' => 'Musa', 'last_name' => 'Yusuf', 'gender' => 'female', 'role_code' => 'CLASS-TEACHER', 'department_code' => 'PRIMARY', 'staff_type' => Staff::TYPE_TEACHING, 'job_title' => 'Class Teacher', 'qualification' => 'nce', 'specialization' => 'Primary Education', 'email' => 'zainab.yusuf.'.Str::slug($school->slug).'@example.com', 'phone' => '+2348022200014', 'salary_scale_code' => 'GL05-S06'],
                ['first_name' => 'Suleiman', 'middle_name' => null, 'last_name' => 'Danlami', 'gender' => 'male', 'role_code' => 'CLASS-TEACHER', 'department_code' => 'PRIMARY', 'staff_type' => Staff::TYPE_TEACHING, 'job_title' => 'Class Teacher', 'qualification' => 'b_ed', 'specialization' => 'Mathematics Education', 'email' => 'suleiman.danlami.'.Str::slug($school->slug).'@example.com', 'phone' => '+2348022200015', 'salary_scale_code' => 'GL05-S08'],
                ['first_name' => 'Rakiya', 'middle_name' => null, 'last_name' => 'Umar', 'gender' => 'female', 'role_code' => 'REGISTRAR', 'department_code' => 'ADMIN', 'staff_type' => Staff::TYPE_NON_TEACHING, 'job_title' => 'Registrar', 'qualification' => 'hnd', 'specialization' => 'Office Administration', 'email' => 'registrar.'.Str::slug($school->slug).'@example.com', 'phone' => '+2348022200016', 'salary_scale_code' => 'GL04-S08'],
            ],
            default => [
                ['first_name' => 'Musa', 'middle_name' => 'Danjuma', 'last_name' => 'Lawal', 'gender' => 'male', 'role_code' => 'PRINCIPAL', 'department_code' => 'ADMIN', 'staff_type' => Staff::TYPE_TEACHING, 'job_title' => 'Principal', 'qualification' => 'm_ed', 'specialization' => 'Educational Management', 'email' => 'principal.'.Str::slug($school->slug).'@example.com', 'phone' => '+2348022200013', 'salary_scale_code' => 'GL08-S08'],
                ['first_name' => 'Fatima', 'middle_name' => 'Aisha', 'last_name' => 'Bello', 'gender' => 'female', 'role_code' => 'VICE-PRINCIPAL-ACADEMIC', 'department_code' => 'SCIENCE', 'staff_type' => Staff::TYPE_TEACHING, 'job_title' => 'Vice Principal Academics', 'qualification' => 'm_sc', 'specialization' => 'Biology Education', 'email' => 'vp.academics.'.Str::slug($school->slug).'@example.com', 'phone' => '+2348022200014', 'salary_scale_code' => 'GL08-S02'],
                ['first_name' => 'Adamu', 'middle_name' => null, 'last_name' => 'Bappa', 'gender' => 'male', 'role_code' => 'HOD', 'department_code' => 'SCIENCE', 'staff_type' => Staff::TYPE_TEACHING, 'job_title' => 'Head of Science Department', 'qualification' => 'b_sc', 'specialization' => 'Physics', 'email' => 'science.hod.'.Str::slug($school->slug).'@example.com', 'phone' => '+2348022200015', 'salary_scale_code' => 'GL06-S10'],
                ['first_name' => 'Aisha', 'middle_name' => 'Kabiru', 'last_name' => 'Sani', 'gender' => 'female', 'role_code' => 'SUBJECT-TEACHER', 'department_code' => 'ARTS', 'staff_type' => Staff::TYPE_TEACHING, 'job_title' => 'English Teacher', 'qualification' => 'b_a', 'specialization' => 'English Language', 'email' => 'aisha.sani.'.Str::slug($school->slug).'@example.com', 'phone' => '+2348022200016', 'salary_scale_code' => 'GL05-S10'],
                ['first_name' => 'Nuhu', 'middle_name' => null, 'last_name' => 'Ahmadu', 'gender' => 'male', 'role_code' => 'SUBJECT-TEACHER', 'department_code' => 'COMMERCIAL', 'staff_type' => Staff::TYPE_TEACHING, 'job_title' => 'Commerce Teacher', 'qualification' => 'b_sc', 'specialization' => 'Economics', 'email' => 'nuhu.ahmadu.'.Str::slug($school->slug).'@example.com', 'phone' => '+2348022200017', 'salary_scale_code' => 'GL05-S08'],
                ['first_name' => 'Hauwa', 'middle_name' => null, 'last_name' => 'Sale', 'gender' => 'female', 'role_code' => 'REGISTRAR', 'department_code' => 'ADMIN', 'staff_type' => Staff::TYPE_NON_TEACHING, 'job_title' => 'Registrar', 'qualification' => 'hnd', 'specialization' => 'Office Administration', 'email' => 'registrar.'.Str::slug($school->slug).'@example.com', 'phone' => '+2348022200018', 'salary_scale_code' => 'GL04-S10'],
            ],
        };

        return collect([...$base, ...$section])
            ->map(function (array $profile, int $index) use ($school): array {
                $payrollSheet = match ($profile['role_code']) {
                    'DIRECTOR', 'PRINCIPAL', 'VICE-PRINCIPAL-ACADEMIC', 'VICE-PRINCIPAL-ADMIN', 'HEAD-NURSERY', 'HEAD-TEACHER' => 'Management',
                    'BURSAR', 'REGISTRAR' => 'Operations',
                    default => $profile['staff_type'] === Staff::TYPE_TEACHING ? 'Teaching Sheet' : 'Operations',
                };

                return $profile + [
                    'date_of_birth' => now()->subYears(32 + $index)->subMonths($index)->toDateString(),
                    'address' => $school->address ?: 'Sample staff quarters, Nigeria',
                    'city' => $school->city ?: 'Abuja',
                    'state' => $school->state ?: 'FCT',
                    'education_school' => self::educationSchool($profile),
                    'trcn_number' => $profile['staff_type'] === Staff::TYPE_TEACHING ? 'TRCN/'.now()->year.'/'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT) : null,
                    'next_of_kin' => self::nextOfKinName($profile),
                    'next_of_kin_phone' => '+23480333000'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'payroll_sheet' => $payrollSheet,
                ];
            })
            ->all();
    }

    /**
     * A same-surname "spouse" name for the next-of-kin field, picked to fit
     * the staff member's own family/cultural background rather than reusing
     * one generic first name for every profile.
     */
    protected static function nextOfKinName(array $profile): string
    {
        $christianSurnames = ['Gyang', 'Vandi', 'Audu'];

        $spouseFirstNames = in_array($profile['last_name'], $christianSurnames, true)
            ? ($profile['gender'] === 'male' ? ['Grace', 'Comfort', 'Ruth'] : ['Emmanuel', 'Solomon', 'Daniel'])
            : ($profile['gender'] === 'male' ? ['Amina', 'Hauwa', 'Zainab'] : ['Ibrahim', 'Musa', 'Abdullahi']);

        $index = crc32($profile['last_name']) % count($spouseFirstNames);

        return $spouseFirstNames[$index].' '.$profile['last_name'];
    }

    /**
     * A plausible Northern Nigerian institution matched to the staff
     * member's qualification level, varied deterministically by surname
     * rather than sending every staff member to the same university.
     */
    protected static function educationSchool(array $profile): string
    {
        $qualification = $profile['qualification'] ?? 'b_ed';
        $seed = crc32($profile['last_name'].$profile['first_name']);

        $pools = [
            'nce' => ['Federal College of Education, Kano', 'Federal College of Education, Katsina', 'Federal College of Education, Yola', 'College of Education, Azare'],
            'hnd' => ['Kaduna Polytechnic', 'Federal Polytechnic, Bauchi', 'Federal Polytechnic, Damaturu', 'Waziri Umaru Federal Polytechnic, Birnin Kebbi'],
            'default' => ['University of Maiduguri', 'Bayero University, Kano', 'Ahmadu Bello University, Zaria', 'Usmanu Danfodiyo University, Sokoto', 'Federal University, Dutse', 'Modibbo Adama University, Yola'],
        ];

        $pool = $pools[$qualification] ?? $pools['default'];

        return $pool[$seed % count($pool)];
    }

    protected static function salaryScaleMatrix(): array
    {
        return [
            '01' => [77500.00, 78370.25, 79240.42, 80110.67, 80980.83, 81851.08, 82721.25, 83591.50, 84461.67, 85331.92],
            '02' => [77922.83, 78992.33, 80061.75, 81131.25, 82200.75, 83270.25, 84339.67, 85409.17, 86478.67, 87548.17],
            '03' => [79415.75, 80700.42, 81985.08, 83269.83, 84554.50, 85839.17, 87123.83, 88408.58, 89693.25, 90977.92],
            '04' => [83796.75, 85289.67, 86782.58, 88275.50, 89768.42, 91261.33, 92754.25, 94247.17, 95740.08, 97233.00],
            '05' => [91975.50, 93794.83, 95614.00, 97433.33, 99252.58, 101071.92, 102891.17, 104710.42, 106529.67, 108349.00],
            '06' => [130994.83, 134335.50, 137676.17, 141016.75, 144357.42, 147698.00, 151038.67, 154379.25, 157719.92, 161060.50],
            '07' => [170985.42, 175441.50, 179897.58, 184353.67, 188809.67, 193265.75, 197721.83, 202177.92, 206634.00, 211090.08],
            '08' => [192087.33, 197331.92, 202576.50, 207821.33, 213065.92, 218310.75, 223555.33, 228800.17, 234044.75, 239289.58],
        ];
    }

    protected static function ensureCurrentSession(School $school): array
    {
        $startYear = now()->month >= 8 ? now()->year : now()->year - 1;
        $endYear = $startYear + 1;

        $academicYear = AcademicYear::query()->updateOrCreate(
            ['school_id' => $school->getKey(), 'name' => "{$startYear}/{$endYear}"],
            ['starts_on' => "{$startYear}-09-08", 'ends_on' => "{$endYear}-07-24", 'is_current' => true, 'is_active' => true],
        );

        $terms = [
            ['name' => 'First Term', 'position' => 1, 'starts_on' => "{$startYear}-09-08", 'ends_on' => "{$startYear}-12-12"],
            ['name' => 'Second Term', 'position' => 2, 'starts_on' => "{$endYear}-01-12", 'ends_on' => "{$endYear}-04-03"],
            ['name' => 'Third Term', 'position' => 3, 'starts_on' => "{$endYear}-04-27", 'ends_on' => "{$endYear}-07-24"],
        ];

        $term = null;

        foreach ($terms as $termData) {
            $term = Term::query()->updateOrCreate(
                ['school_id' => $school->getKey(), 'academic_year_id' => $academicYear->getKey(), 'name' => $termData['name']],
                [
                    'position' => $termData['position'],
                    'starts_on' => $termData['starts_on'],
                    'ends_on' => $termData['ends_on'],
                    'is_current' => $termData['name'] === 'Third Term',
                    'is_active' => true,
                ],
            );
        }

        return [$academicYear, $term];
    }

    protected static function ensureClasses(School $school): Collection
    {
        $classes = SchoolClass::query()->where('school_id', $school->getKey())->orderBy('level')->get();

        if ($classes->isNotEmpty()) {
            return $classes;
        }

        $templates = SchoolStructurePreset::defaultTemplatesForDivision($school->division);
        $templates = $templates === [] ? ['nursery', 'primary', 'secondary'] : $templates;

        foreach (SchoolStructurePreset::defaults($templates) as $class) {
            SchoolClass::query()->updateOrCreate(
                ['school_id' => $school->getKey(), 'code' => $class['code']],
                ['name' => $class['name'], 'level' => $class['level'], 'department' => $class['department'] ?: null, 'is_active' => true],
            );
        }

        return SchoolClass::query()->where('school_id', $school->getKey())->orderBy('level')->get();
    }

    protected static function ensureSections(School $school, Collection $classes): array
    {
        $sections = [];

        foreach ($classes as $class) {
            $sections[$class->getKey()] = ClassSection::query()->updateOrCreate(
                ['school_id' => $school->getKey(), 'school_class_id' => $class->getKey(), 'code' => "{$class->code}-A"],
                ['name' => 'A', 'capacity' => 35, 'is_active' => true],
            );
        }

        return $sections;
    }

    protected static function ensureSubjects(School $school): Collection
    {
        $subjects = Subject::query()->where('school_id', $school->getKey())->orderBy('id')->get();

        if ($subjects->isNotEmpty()) {
            return $subjects;
        }

        $templates = SubjectPreset::defaultTemplatesForDivision($school->division);
        $templates = $templates === [] ? ['nursery', 'primary', 'junior_secondary', 'senior_secondary', 'common'] : $templates;

        foreach (SubjectPreset::defaults($templates) as $subject) {
            Subject::query()->updateOrCreate(
                ['school_id' => $school->getKey(), 'code' => $subject['code']],
                ['name' => $subject['name'], 'department' => $subject['department'] ?: null, 'is_active' => true],
            );
        }

        return Subject::query()->where('school_id', $school->getKey())->orderBy('id')->get();
    }

    protected static function subjectsPerClass(School $school): int
    {
        return match ($school->division) {
            'nursery' => 5,
            'primary' => 7,
            default => 8,
        };
    }

    protected static function weeklyPeriods(string $subjectCode): int
    {
        return in_array($subjectCode, ['ENG', 'MTH', 'NUM', 'LET', 'PHN'], true) ? 5 : 2;
    }

    protected static function staffNumber(School $school, int $index): string
    {
        return sprintf('%s/STF/%s/%03d', $school->code ?: 'SCH', strtoupper((string) ($school->division ?: 'GEN')), $index);
    }
}
