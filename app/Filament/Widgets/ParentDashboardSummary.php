<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ParentInvoices\ParentInvoiceResource;
use App\Filament\Resources\ParentReportCards\ParentReportCardResource;
use App\Models\Guardian;
use App\Models\StudentInvoice;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class ParentDashboardSummary extends Widget
{
    protected string $view = 'filament.widgets.parent-dashboard-summary';

    protected static bool $isLazy = false;

    protected static ?int $sort = -25;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) Filament::auth()->user()?->hasSchoolRole(Filament::getTenant(), 'parent');
    }

    protected function getViewData(): array
    {
        $user = Filament::auth()->user();
        $tenant = Filament::getTenant();
        $studentIds = Guardian::query()
            ->where('school_id', $tenant?->getKey())
            ->where('user_id', $user?->getKey())
            ->with('studentLinks')
            ->get()
            ->flatMap(fn (Guardian $guardian) => $guardian->studentLinks->pluck('student_id'))
            ->filter()
            ->unique()
            ->values();

        $invoiceQuery = StudentInvoice::query()
            ->where('school_id', $tenant?->getKey())
            ->whereIn('student_id', $studentIds);

        return [
            'schoolName' => $tenant?->baseSchoolName() ?? 'School Dice',
            'sectionName' => $tenant?->divisionLabel(),
            'childrenCount' => $studentIds->count(),
            'unpaidInvoices' => (clone $invoiceQuery)->where('balance', '>', 0)->where('status', '!=', 'cancelled')->count(),
            'outstandingBalance' => (float) (clone $invoiceQuery)->where('status', '!=', 'cancelled')->sum('balance'),
            'invoiceUrl' => ParentInvoiceResource::getUrl('index'),
            'resultUrl' => ParentReportCardResource::getUrl('index'),
        ];
    }
}
