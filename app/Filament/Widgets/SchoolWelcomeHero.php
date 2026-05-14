<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\FeeStructures\FeeStructureResource;
use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Filament\Resources\Staff\StaffResource;
use App\Filament\Resources\StudentInvoices\StudentInvoiceResource;
use App\Filament\Resources\Students\StudentResource;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;

class SchoolWelcomeHero extends Widget
{
    protected string $view = 'filament.widgets.school-welcome-hero';

    protected static ?int $sort = -30;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return ! Filament::auth()->user()?->hasSchoolRole(Filament::getTenant(), 'teacher');
    }

    protected function getViewData(): array
    {
        $tenant = Filament::getTenant();
        $schoolId = $tenant?->getKey();

        $setupChecks = [
            [
                'label' => 'Classes ready',
                'description' => 'Create classes and arms before admissions.',
                'done' => $schoolId ? SchoolClass::query()->where('school_id', $schoolId)->exists() : false,
            ],
            [
                'label' => 'Students admitted',
                'description' => 'Start student records and class placements.',
                'done' => $schoolId ? Student::query()->where('school_id', $schoolId)->exists() : false,
            ],
            [
                'label' => 'Staff captured',
                'description' => 'Add teaching and non-teaching staff.',
                'done' => $schoolId ? Staff::query()->where('school_id', $schoolId)->exists() : false,
            ],
            [
                'label' => 'Fee setup done',
                'description' => 'Add fee structures before generating invoices.',
                'done' => $schoolId ? FeeStructure::query()->where('school_id', $schoolId)->exists() : false,
            ],
        ];

        $completed = collect($setupChecks)->where('done', true)->count();

        return [
            'schoolName' => $tenant?->baseSchoolName() ?? 'School Dice',
            'sectionName' => $tenant?->divisionLabel(),
            'schoolCode' => $tenant?->code,
            'logoUrl' => self::resolveLogoUrl($tenant?->logo_path),
            'progress' => (int) round(($completed / count($setupChecks)) * 100),
            'actions' => [
                [
                    'label' => 'Admit Student',
                    'description' => 'Create a learner profile and guardian record.',
                    'url' => StudentResource::getUrl('create'),
                    'icon' => 'heroicon-o-academic-cap',
                ],
                [
                    'label' => 'Add Staff',
                    'description' => 'Capture staff biodata, photo, and work details.',
                    'url' => StaffResource::getUrl('create'),
                    'icon' => 'heroicon-o-users',
                ],
                [
                    'label' => 'Fee Structure',
                    'description' => 'Set class charges for the current session or term.',
                    'url' => FeeStructureResource::getUrl('index'),
                    'icon' => 'heroicon-o-banknotes',
                ],
                [
                    'label' => 'Generate Invoice',
                    'description' => 'Create single, emergency, or class invoices.',
                    'url' => StudentInvoiceResource::getUrl('index'),
                    'icon' => 'heroicon-o-document-text',
                ],
                [
                    'label' => 'Classes & Arms',
                    'description' => 'Organize class groups and form teachers.',
                    'url' => SchoolClassResource::getUrl('index'),
                    'icon' => 'heroicon-o-squares-2x2',
                ],
            ],
            'setupChecks' => $setupChecks,
        ];
    }

    protected static function resolveLogoUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
