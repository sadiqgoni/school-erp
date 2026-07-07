<?php

namespace App\Filament\Resources\Concerns;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

trait SchoolPanelResource
{
    protected static array $teacherResources = [
        'App\\Filament\\Resources\\Assignments\\AssignmentResource',
        'App\\Filament\\Resources\\ClassSubjects\\ClassSubjectResource',
        'App\\Filament\\Resources\\Enrollments\\EnrollmentResource',
        'App\\Filament\\Resources\\Notices\\NoticeResource',
        'App\\Filament\\Resources\\ReportCards\\ReportCardResource',
        'App\\Filament\\Resources\\StudentScores\\StudentScoreResource',
        'App\\Filament\\Resources\\TimetableEntries\\TimetableEntryResource',
    ];

    protected static array $teacherWritableResources = [
        'App\\Filament\\Resources\\Assignments\\AssignmentResource',
        'App\\Filament\\Resources\\Notices\\NoticeResource',
        'App\\Filament\\Resources\\TimetableEntries\\TimetableEntryResource',
    ];

    protected static array $financeResources = [
        'App\\Filament\\Resources\\AccountTransactions\\AccountTransactionResource',
        'App\\Filament\\Resources\\AccountTransfers\\AccountTransferResource',
        'App\\Filament\\Resources\\BankAccounts\\BankAccountResource',
        'App\\Filament\\Resources\\ExpenseCategories\\ExpenseCategoryResource',
        'App\\Filament\\Resources\\Expenses\\ExpenseResource',
        'App\\Filament\\Resources\\FeePayments\\FeePaymentResource',
        'App\\Filament\\Resources\\FeeStructures\\FeeStructureResource',
        'App\\Filament\\Resources\\FeeTypes\\FeeTypeResource',
        'App\\Filament\\Resources\\LedgerAccounts\\LedgerAccountResource',
        'App\\Filament\\Resources\\PayrollItemTypes\\PayrollItemTypeResource',
        'App\\Filament\\Resources\\PayrollSheets\\PayrollSheetResource',
        'App\\Filament\\Resources\\PayrollSnapshots\\PayrollSnapshotResource',
        'App\\Filament\\Resources\\SalaryPostings\\SalaryPostingResource',
        'App\\Filament\\Resources\\SalaryTemplates\\SalaryTemplateResource',
        'App\\Filament\\Resources\\StaffBanks\\StaffBankResource',
        'App\\Filament\\Resources\\StudentDiscounts\\StudentDiscountResource',
        'App\\Filament\\Resources\\StudentInvoices\\StudentInvoiceResource',
    ];

    public static function canAccess(): bool
    {
        return static::canAccessSchoolResource() && parent::canAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (! static::isSchoolPanel() || ! parent::shouldRegisterNavigation()) {
            return false;
        }

        if (static::isParentUser()) {
            return false;
        }

        if (static::isFinanceUser()) {
            return static::isFinanceResource();
        }

        return ! static::isTeacherUser() || static::isTeacherResource();
    }

    public static function canViewAny(): bool
    {
        return static::canAccessSchoolResource() && parent::canViewAny();
    }

    public static function canCreate(): bool
    {
        if (! static::canAccessSchoolResource()) {
            return false;
        }

        if (static::isParentUser()) {
            return false;
        }

        if (static::isTeacherUser()) {
            return static::isTeacherWritableResource() && parent::canCreate();
        }

        return parent::canCreate();
    }

    public static function canView(Model $record): bool
    {
        return static::canAccessSchoolResource() && parent::canView($record);
    }

    public static function canEdit(Model $record): bool
    {
        if (! static::canAccessSchoolResource()) {
            return false;
        }

        if (static::isParentUser()) {
            return false;
        }

        if (static::isTeacherUser()) {
            return static::isTeacherWritableResource() && parent::canEdit($record);
        }

        return parent::canEdit($record);
    }

    protected static function isSchoolPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'school';
    }

    protected static function canAccessSchoolResource(): bool
    {
        if (! static::isSchoolPanel()) {
            return false;
        }

        if (static::isParentUser()) {
            return false;
        }

        if (static::isFinanceUser()) {
            return static::isFinanceResource();
        }

        return ! static::isTeacherUser() || static::isTeacherResource();
    }

    protected static function isTeacherUser(): bool
    {
        $user = Filament::auth()->user();

        return (bool) $user?->hasSchoolRole(Filament::getTenant(), 'teacher');
    }

    protected static function isFinanceUser(): bool
    {
        $user = Filament::auth()->user();

        return (bool) $user?->hasSchoolRole(Filament::getTenant(), 'finance');
    }

    protected static function isParentUser(): bool
    {
        $user = Filament::auth()->user();

        return (bool) $user?->hasSchoolRole(Filament::getTenant(), 'parent');
    }

    protected static function isTeacherResource(): bool
    {
        return in_array(static::class, static::$teacherResources, true);
    }

    protected static function isTeacherWritableResource(): bool
    {
        return in_array(static::class, static::$teacherWritableResources, true);
    }

    protected static function isFinanceResource(): bool
    {
        return in_array(static::class, static::$financeResources, true);
    }
}
