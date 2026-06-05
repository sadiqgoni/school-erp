<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ParentInvoices\ParentInvoiceResource;
use App\Filament\Resources\ParentReportCards\ParentReportCardResource;
use App\Models\Guardian;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ParentPortal extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'My Children';

    protected static ?string $title = 'My Children';

    protected static string|\UnitEnum|null $navigationGroup = 'Parent Portal';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.parent-portal';

    public static function canAccess(): bool
    {
        return (bool) Filament::auth()->user()?->hasSchoolRole(Filament::getTenant(), 'parent');
    }

    protected function getViewData(): array
    {
        $user = Filament::auth()->user();
        $tenant = Filament::getTenant();

        $guardians = Guardian::query()
            ->with([
                'studentLinks.student.enrollments.academicYear',
                'studentLinks.student.enrollments.term',
                'studentLinks.student.enrollments.schoolClass',
                'studentLinks.student.enrollments.classSection',
            ])
            ->where('school_id', $tenant?->getKey())
            ->where('user_id', $user?->getKey())
            ->get();

        $students = $guardians
            ->flatMap(fn (Guardian $guardian) => $guardian->studentLinks)
            ->pluck('student')
            ->filter()
            ->unique('id')
            ->values();

        return [
            'guardians' => $guardians,
            'students' => $students,
            'invoiceUrl' => ParentInvoiceResource::getUrl('index'),
            'resultUrl' => ParentReportCardResource::getUrl('index'),
        ];
    }
}
