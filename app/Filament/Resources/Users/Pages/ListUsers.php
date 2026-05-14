<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\School;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getTabs(): array
    {
        if (Filament::getCurrentPanel()?->getId() === 'school') {
            $tenant = Filament::getTenant();

            return [
                'all' => Tab::make('All users')
                    ->badge(User::query()->whereHas('schools', fn (Builder $query) => $query->whereKey($tenant?->getKey()))->count()),
                'admins' => Tab::make('School admins')
                    ->badge(User::query()->whereHas('schools', fn (Builder $query) => $query->whereKey($tenant?->getKey())->where('school_user.role', 'school_admin'))->count())
                    ->query(fn (Builder $query): Builder => $query->whereHas('schools', fn (Builder $query) => $query->whereKey($tenant?->getKey())->where('school_user.role', 'school_admin'))),
                'teachers' => Tab::make('Teachers')
                    ->badge(User::query()->whereHas('schools', fn (Builder $query) => $query->whereKey($tenant?->getKey())->where('school_user.role', 'teacher'))->count())
                    ->query(fn (Builder $query): Builder => $query->whereHas('schools', fn (Builder $query) => $query->whereKey($tenant?->getKey())->where('school_user.role', 'teacher'))),
                'staff' => Tab::make('Staff')
                    ->badge(User::query()->whereHas('schools', fn (Builder $query) => $query->whereKey($tenant?->getKey())->where('school_user.role', 'staff'))->count())
                    ->query(fn (Builder $query): Builder => $query->whereHas('schools', fn (Builder $query) => $query->whereKey($tenant?->getKey())->where('school_user.role', 'staff'))),
            ];
        }

        $tabs = [
            'all' => Tab::make('All users')
                ->badge(User::query()->count()),
            'platform_admins' => Tab::make('Platform admins')
                ->badge(User::query()->where('is_platform_admin', true)->count())
                ->query(fn (Builder $query): Builder => $query->where('is_platform_admin', true)),
        ];

        School::query()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->each(function (School $school) use (&$tabs): void {
                $tabs['school_'.$school->getKey()] = Tab::make($school->name)
                    ->badge($school->users_count)
                    ->query(fn (Builder $query): Builder => $query->whereHas(
                        'schools',
                        fn (Builder $query): Builder => $query->whereKey($school->getKey()),
                    ));
            });

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
