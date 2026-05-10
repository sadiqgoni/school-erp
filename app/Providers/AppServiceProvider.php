<?php

namespace App\Providers;

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
use App\Observers\AuditModelObserver;
use App\Support\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ($this->auditedModels() as $model) {
            $model::observe(AuditModelObserver::class);
        }

        Event::listen(Login::class, function (Login $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            AuditLogger::log('login', 'Signed in', user: $event->user);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            AuditLogger::log('logout', 'Signed out', user: $event->user);
        });
    }

    /**
     * @return array<int, class-string>
     */
    protected function auditedModels(): array
    {
        return [
            AcademicYear::class,
            AssessmentComponent::class,
            ClassSection::class,
            ClassSubject::class,
            CompiledResult::class,
            Department::class,
            Enrollment::class,
            Exam::class,
            Expense::class,
            ExpenseCategory::class,
            FeePayment::class,
            FeeStructure::class,
            FeeType::class,
            GradeScale::class,
            Guardian::class,
            GuardianStudent::class,
            ReportCard::class,
            School::class,
            SchoolClass::class,
            Staff::class,
            StaffAttendance::class,
            StaffRole::class,
            StaffRoleAssignment::class,
            Student::class,
            StudentAttendance::class,
            StudentAttendanceRecord::class,
            StudentInvoice::class,
            StudentInvoiceItem::class,
            StudentScore::class,
            Subject::class,
            TeachingAssignment::class,
            Term::class,
            User::class,
        ];
    }
}
