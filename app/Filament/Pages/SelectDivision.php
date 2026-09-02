<?php

namespace App\Filament\Pages;

use App\Models\School;
use App\Support\CurrentDivision;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Shown when a user has more than one division available under the current
 * (parent-school) tenant and hasn't picked one for this session yet — see
 * App\Http\Middleware\EnsureDivisionSelected, which redirects here.
 *
 * Not registered in the panel navigation or under SchoolPanelResource; this
 * page exists purely as the landing spot the gate middleware sends users to.
 */
class SelectDivision extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $title = 'Choose a section';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.select-division';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        $tenant = Filament::getTenant();

        return $user && $tenant instanceof School;
    }

    protected function getViewData(): array
    {
        $user = Filament::auth()->user();
        $tenant = Filament::getTenant();

        return [
            'divisions' => ($user && $tenant instanceof School)
                ? CurrentDivision::availableFor($user, $tenant)
                : collect(),
        ];
    }

    public function select(int $divisionId): void
    {
        $tenant = Filament::getTenant();
        $user = Filament::auth()->user();

        if (! $tenant instanceof School || ! $user) {
            return;
        }

        $division = CurrentDivision::availableFor($user, $tenant)->firstWhere('id', $divisionId);

        if (! $division) {
            return;
        }

        CurrentDivision::set($division, $tenant);

        $this->redirect(Dashboard::getUrl());
    }
}
