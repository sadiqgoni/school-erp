<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\AssessmentComponent;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\CompiledResult;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\FeeType;
use App\Models\GradeScale;
use App\Models\Guardian;
use App\Models\GuardianStudent;
use App\Models\ReportCard;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StaffRole;
use App\Models\StaffRoleAssignment;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentAttendanceRecord;
use App\Models\StudentInvoice;
use App\Models\StudentInvoiceItem;
use App\Models\StudentScore;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make('password'),
                'is_platform_admin' => true,
                'is_active' => true,
            ],
        );

        $school = School::query()->updateOrCreate(
            ['code' => 'DEMO'],
            [
                'name' => 'Demo International School',
                'slug' => 'demo-international-school',
                'email' => 'school@example.com',
                'phone' => '+2348000000000',
                'address' => 'School address goes here',
                'city' => 'Maiduguri',
                'state' => 'Borno',
                'country' => 'Nigeria',
                'primary_color' => '#0f766e',
                'subscription_plan' => 'trial',
                'student_limit' => 1000,
                'enabled_modules' => [
                    'students',
                    'staff',
                    'attendance',
                    'fees',
                    'exams',
                    'library',
                    'communications',
                ],
                'is_active' => true,
            ],
        );

        $admin->schools()->syncWithoutDetaching([
            $school->id => [
                'role' => 'platform_admin',
                'is_primary' => true,
            ],
        ]);

        $schoolAdmin = User::query()->updateOrCreate(
            ['email' => 'principal@demo-school.test'],
            [
                'name' => 'Demo School Admin',
                'password' => Hash::make('password'),
                'is_platform_admin' => false,
                'is_active' => true,
            ],
        );

        $schoolAdmin->schools()->syncWithoutDetaching([
            $school->id => [
                'role' => 'school_admin',
                'is_primary' => true,
            ],
        ]);

        $academicYear = AcademicYear::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'name' => '2026/2027',
            ],
            [
                'starts_on' => '2026-09-01',
                'ends_on' => '2027-07-31',
                'is_current' => true,
                'is_active' => true,
            ],
        );

        foreach ([
            ['name' => 'First Term', 'position' => 1, 'starts_on' => '2026-09-01', 'ends_on' => '2026-12-18', 'is_current' => true],
            ['name' => 'Second Term', 'position' => 2, 'starts_on' => '2027-01-11', 'ends_on' => '2027-04-09', 'is_current' => false],
            ['name' => 'Third Term', 'position' => 3, 'starts_on' => '2027-04-26', 'ends_on' => '2027-07-31', 'is_current' => false],
        ] as $term) {
            Term::query()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'academic_year_id' => $academicYear->id,
                    'position' => $term['position'],
                ],
                $term + ['is_active' => true],
            );
        }

        $classes = collect([
            ['name' => 'JSS 1', 'code' => 'JSS1', 'level' => 1, 'department' => 'Junior Secondary'],
            ['name' => 'JSS 2', 'code' => 'JSS2', 'level' => 2, 'department' => 'Junior Secondary'],
            ['name' => 'JSS 3', 'code' => 'JSS3', 'level' => 3, 'department' => 'Junior Secondary'],
        ])->map(fn (array $class) => SchoolClass::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'code' => $class['code'],
            ],
            $class + ['is_active' => true],
        ));

        foreach ($classes as $class) {
            foreach (['A', 'B'] as $section) {
                ClassSection::query()->updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'school_class_id' => $class->id,
                        'code' => "{$class->code}-{$section}",
                    ],
                    [
                        'name' => $section,
                        'capacity' => 40,
                        'is_active' => true,
                    ],
                );
            }
        }

        $subjects = collect([
            ['name' => 'Mathematics', 'code' => 'MTH', 'department' => 'Core'],
            ['name' => 'English Language', 'code' => 'ENG', 'department' => 'Core'],
            ['name' => 'Basic Science', 'code' => 'BSC', 'department' => 'Sciences'],
        ])->map(fn (array $subject) => Subject::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'code' => $subject['code'],
            ],
            $subject + ['is_active' => true],
        ));

        foreach ($classes as $class) {
            foreach ($subjects as $subject) {
                ClassSubject::query()->updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'school_class_id' => $class->id,
                        'subject_id' => $subject->id,
                    ],
                    [
                        'is_compulsory' => true,
                        'weekly_periods' => 4,
                        'is_active' => true,
                    ],
                );
            }
        }

        $student = Student::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'admission_number' => 'DEMO/2026/001',
            ],
            [
                'first_name' => 'Aisha',
                'middle_name' => null,
                'last_name' => 'Musa',
                'date_of_birth' => '2014-03-12',
                'gender' => 'female',
                'blood_group' => 'O+',
                'religion' => 'Islam',
                'phone' => null,
                'email' => null,
                'address' => 'Student address goes here',
                'city' => 'Maiduguri',
                'state' => 'Borno',
                'country' => 'Nigeria',
                'admitted_on' => '2026-09-01',
                'status' => 'active',
                'previous_school' => 'Primary School',
                'medical_notes' => null,
            ],
        );

        $guardian = Guardian::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'phone' => '+2348011111111',
            ],
            [
                'name' => 'Fatima Musa',
                'alternate_phone' => '+2348022222222',
                'email' => 'guardian@example.com',
                'occupation' => 'Trader',
                'address' => 'Guardian address goes here',
                'is_active' => true,
            ],
        );

        GuardianStudent::query()->updateOrCreate(
            [
                'guardian_id' => $guardian->id,
                'student_id' => $student->id,
            ],
            [
                'school_id' => $school->id,
                'relationship' => 'mother',
                'is_primary_contact' => true,
                'can_pick_up' => true,
                'receives_sms' => true,
            ],
        );

        $jssOne = $classes->firstWhere('code', 'JSS1');
        $jssOneA = ClassSection::query()
            ->where('school_id', $school->id)
            ->where('school_class_id', $jssOne->id)
            ->where('code', 'JSS1-A')
            ->first();

        Enrollment::query()->updateOrCreate(
            [
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
            ],
            [
                'school_id' => $school->id,
                'term_id' => Term::query()
                    ->where('school_id', $school->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('is_current', true)
                    ->value('id'),
                'school_class_id' => $jssOne->id,
                'class_section_id' => $jssOneA?->id,
                'enrolled_on' => '2026-09-01',
                'status' => 'active',
                'remarks' => 'Seeded demo enrollment',
            ],
        );

        $academicsDepartment = Department::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'code' => 'ACA',
            ],
            [
                'name' => 'Academics',
                'description' => 'Teaching and learning department',
                'is_active' => true,
            ],
        );

        $teacherRole = StaffRole::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'code' => 'CLASS_TEACHER',
            ],
            [
                'name' => 'Class Teacher',
                'description' => 'Manages class attendance, remarks, and student follow-up',
                'permissions' => [
                    'attendance' => true,
                    'results' => true,
                    'homework' => true,
                ],
                'is_active' => true,
            ],
        );

        $staff = Staff::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'staff_number' => 'STAFF/2026/001',
            ],
            [
                'department_id' => $academicsDepartment->id,
                'first_name' => 'Ibrahim',
                'middle_name' => null,
                'last_name' => 'Ali',
                'gender' => 'male',
                'date_of_birth' => '1990-06-15',
                'phone' => '+2348033333333',
                'email' => 'teacher@example.com',
                'address' => 'Staff address goes here',
                'city' => 'Maiduguri',
                'state' => 'Borno',
                'country' => 'Nigeria',
                'employment_type' => 'full_time',
                'job_title' => 'Mathematics Teacher',
                'hire_date' => '2026-08-15',
                'basic_salary' => 120000,
                'bank_name' => 'Demo Bank',
                'bank_account_name' => 'Ibrahim Ali',
                'bank_account_number' => '0123456789',
                'status' => 'active',
            ],
        );

        StaffRoleAssignment::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'staff_id' => $staff->id,
                'staff_role_id' => $teacherRole->id,
            ],
            [
                'assigned_on' => '2026-08-15',
                'is_primary' => true,
                'is_active' => true,
            ],
        );

        $mathematics = $subjects->firstWhere('code', 'MTH');

        TeachingAssignment::query()->updateOrCreate(
            [
                'staff_id' => $staff->id,
                'academic_year_id' => $academicYear->id,
                'school_class_id' => $jssOne->id,
                'class_section_id' => $jssOneA?->id,
                'subject_id' => $mathematics->id,
            ],
            [
                'school_id' => $school->id,
                'term_id' => Term::query()
                    ->where('school_id', $school->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('is_current', true)
                    ->value('id'),
                'is_class_teacher' => true,
                'is_active' => true,
            ],
        );

        $studentAttendance = StudentAttendance::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'school_class_id' => $jssOne->id,
                'class_section_id' => $jssOneA?->id,
                'attendance_date' => '2026-09-02',
                'session' => 'morning',
            ],
            [
                'academic_year_id' => $academicYear->id,
                'term_id' => Term::query()
                    ->where('school_id', $school->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('is_current', true)
                    ->value('id'),
                'taken_by_id' => $staff->id,
                'status' => 'submitted',
                'remarks' => 'Seeded demo class attendance',
            ],
        );

        StudentAttendanceRecord::query()->updateOrCreate(
            [
                'student_attendance_id' => $studentAttendance->id,
                'student_id' => $student->id,
            ],
            [
                'school_id' => $school->id,
                'status' => 'present',
                'arrival_time' => '07:42',
                'remarks' => null,
            ],
        );

        StaffAttendance::query()->updateOrCreate(
            [
                'staff_id' => $staff->id,
                'attendance_date' => '2026-09-02',
            ],
            [
                'school_id' => $school->id,
                'status' => 'present',
                'clock_in' => '07:25',
                'clock_out' => '14:10',
                'recorded_by_id' => $admin->id,
                'remarks' => 'Seeded demo staff attendance',
            ],
        );

        $tuition = FeeType::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'code' => 'TUITION',
            ],
            [
                'name' => 'Tuition Fee',
                'description' => 'Core term tuition fee',
                'is_required' => true,
                'is_active' => true,
            ],
        );

        $currentTerm = Term::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('is_current', true)
            ->first();

        FeeStructure::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'academic_year_id' => $academicYear->id,
                'term_id' => $currentTerm?->id,
                'school_class_id' => $jssOne->id,
                'fee_type_id' => $tuition->id,
            ],
            [
                'amount' => 45000,
                'due_date' => '2026-09-30',
                'is_active' => true,
            ],
        );

        $invoice = StudentInvoice::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'invoice_number' => 'INV-2026-0001',
            ],
            [
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'term_id' => $currentTerm?->id,
                'invoice_date' => '2026-09-01',
                'due_date' => '2026-09-30',
                'subtotal' => 45000,
                'discount' => 0,
                'total' => 45000,
                'amount_paid' => 20000,
                'balance' => 25000,
                'status' => 'partial',
                'notes' => 'Seeded demo student invoice',
            ],
        );

        StudentInvoiceItem::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'student_invoice_id' => $invoice->id,
                'fee_type_id' => $tuition->id,
            ],
            [
                'description' => 'First Term Tuition Fee',
                'amount' => 45000,
            ],
        );

        FeePayment::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'receipt_number' => 'RCT-2026-0001',
            ],
            [
                'student_invoice_id' => $invoice->id,
                'student_id' => $student->id,
                'payment_date' => '2026-09-05',
                'amount' => 20000,
                'payment_method' => 'cash',
                'reference' => null,
                'received_by_id' => $admin->id,
                'status' => 'confirmed',
                'notes' => 'Seeded demo payment',
            ],
        );

        $utilities = ExpenseCategory::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'code' => 'UTIL',
            ],
            [
                'name' => 'Utilities',
                'description' => 'Electricity, water, internet and similar running costs',
                'is_active' => true,
            ],
        );

        Expense::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'expense_number' => 'EXP-2026-0001',
            ],
            [
                'expense_category_id' => $utilities->id,
                'expense_date' => '2026-09-06',
                'payee' => 'Power Company',
                'description' => 'Electricity bill',
                'amount' => 15000,
                'payment_method' => 'bank_transfer',
                'reference' => 'DEMO-UTIL-001',
                'recorded_by_id' => $admin->id,
                'status' => 'paid',
                'notes' => 'Seeded demo expense',
            ],
        );

        foreach ([
            ['grade' => 'A', 'min_score' => 70, 'max_score' => 100, 'grade_point' => 5, 'remark' => 'Excellent'],
            ['grade' => 'B', 'min_score' => 60, 'max_score' => 69.99, 'grade_point' => 4, 'remark' => 'Very good'],
            ['grade' => 'C', 'min_score' => 50, 'max_score' => 59.99, 'grade_point' => 3, 'remark' => 'Good'],
            ['grade' => 'D', 'min_score' => 40, 'max_score' => 49.99, 'grade_point' => 2, 'remark' => 'Pass'],
            ['grade' => 'F', 'min_score' => 0, 'max_score' => 39.99, 'grade_point' => 0, 'remark' => 'Fail'],
        ] as $scale) {
            GradeScale::query()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => 'Default',
                    'grade' => $scale['grade'],
                ],
                $scale + ['is_active' => true],
            );
        }

        $exam = Exam::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'academic_year_id' => $academicYear->id,
                'term_id' => $currentTerm?->id,
                'name' => 'First Term Examination',
            ],
            [
                'type' => 'term',
                'starts_on' => '2026-12-01',
                'ends_on' => '2026-12-12',
                'status' => 'open',
                'remarks' => 'Seeded demo exam',
            ],
        );

        $ca = AssessmentComponent::query()->updateOrCreate(
            [
                'exam_id' => $exam->id,
                'code' => 'CA',
            ],
            [
                'school_id' => $school->id,
                'name' => 'Continuous Assessment',
                'max_score' => 40,
                'position' => 1,
                'is_active' => true,
            ],
        );

        $mainExam = AssessmentComponent::query()->updateOrCreate(
            [
                'exam_id' => $exam->id,
                'code' => 'EXAM',
            ],
            [
                'school_id' => $school->id,
                'name' => 'Main Examination',
                'max_score' => 60,
                'position' => 2,
                'is_active' => true,
            ],
        );

        StudentScore::query()->updateOrCreate(
            [
                'assessment_component_id' => $ca->id,
                'student_id' => $student->id,
                'subject_id' => $mathematics->id,
            ],
            [
                'school_id' => $school->id,
                'exam_id' => $exam->id,
                'staff_id' => $staff->id,
                'score' => 32,
                'status' => 'approved',
            ],
        );

        StudentScore::query()->updateOrCreate(
            [
                'assessment_component_id' => $mainExam->id,
                'student_id' => $student->id,
                'subject_id' => $mathematics->id,
            ],
            [
                'school_id' => $school->id,
                'exam_id' => $exam->id,
                'staff_id' => $staff->id,
                'score' => 48,
                'status' => 'approved',
            ],
        );

        CompiledResult::query()->updateOrCreate(
            [
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'subject_id' => $mathematics->id,
            ],
            [
                'school_id' => $school->id,
                'total_score' => 80,
                'grade' => 'A',
                'grade_point' => 5,
                'remark' => 'Excellent',
                'position' => 1,
                'status' => 'compiled',
            ],
        );

        ReportCard::query()->updateOrCreate(
            [
                'exam_id' => $exam->id,
                'student_id' => $student->id,
            ],
            [
                'school_id' => $school->id,
                'academic_year_id' => $academicYear->id,
                'term_id' => $currentTerm?->id,
                'total_score' => 80,
                'average_score' => 80,
                'position' => 1,
                'teacher_comment' => 'A focused and promising learner.',
                'principal_comment' => 'Excellent performance. Keep it up.',
                'status' => 'approved',
                'published_at' => null,
            ],
        );
    }
}
