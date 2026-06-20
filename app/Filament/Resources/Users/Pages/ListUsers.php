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
                    ->badge(self::schoolPanelUsersQuery(User::query(), $tenant?->getKey())->count()),
                'admins' => Tab::make('Admins')
                    ->badge(self::schoolRoleUsersQuery(User::query(), $tenant?->getKey(), [User::SCHOOL_ROLE_ADMIN, 'school_admin'])->count())
                    ->query(fn (Builder $query): Builder => self::schoolRoleUsersQuery($query, $tenant?->getKey(), [User::SCHOOL_ROLE_ADMIN, 'school_admin'])),
                'teachers' => Tab::make('Teachers')
                    ->badge(self::schoolRoleUsersQuery(User::query(), $tenant?->getKey(), User::SCHOOL_ROLE_TEACHER)->count())
                    ->query(fn (Builder $query): Builder => self::schoolRoleUsersQuery($query, $tenant?->getKey(), User::SCHOOL_ROLE_TEACHER)),
                'staff' => Tab::make('Staff')
                    ->badge(self::schoolRoleUsersQuery(User::query(), $tenant?->getKey(), User::SCHOOL_ROLE_STAFF)->count())
                    ->query(fn (Builder $query): Builder => self::schoolRoleUsersQuery($query, $tenant?->getKey(), User::SCHOOL_ROLE_STAFF)),
                'parents' => Tab::make('Parents')
                    ->badge(self::parentUsersQuery(User::query(), $tenant?->getKey())->count())
                    ->query(fn (Builder $query): Builder => self::parentUsersQuery($query, $tenant?->getKey())),
            ];
        }

        $tabs = [
            'all' => Tab::make('All users')
                ->badge(User::query()->count()),
            'superadmins' => Tab::make('Superadmins')
                ->badge(self::superadminUsersQuery(User::query())->count())
                ->query(fn (Builder $query): Builder => self::superadminUsersQuery($query)),
        ];

        School::query()
            ->withoutGlobalScopes()
            ->whereNull('parent_school_id')
            ->with('divisions')
            ->orderBy('name')
            ->get()
            ->each(function (School $school) use (&$tabs): void {
                $schoolIds = collect([$school->getKey()])
                    ->merge($school->divisions->pluck('id'))
                    ->values()
                    ->all();

                $tabs['school_'.$school->getKey()] = Tab::make($school->name . ' · All Sections')
                    ->badge(User::query()
                        ->whereHas('schools', fn (Builder $query): Builder => $query->whereIn('schools.id', $schoolIds))
                        ->count())
                    ->query(fn (Builder $query): Builder => $query->whereHas(
                        'schools',
                        fn (Builder $query): Builder => $query->whereIn('schools.id', $schoolIds),
                    ));

                $school->divisions
                    ->sortBy('division')
                    ->each(function (School $divisionSchool) use (&$tabs, $school): void {
                        $label = School::DIVISIONS[$divisionSchool->division] ?? ucfirst((string) $divisionSchool->division);

                        $tabs['school_'.$school->getKey().'_'.$divisionSchool->division] = Tab::make($school->name . ' · ' . $label)
                            ->badge(
                                User::query()
                                    ->whereHas('schools', fn (Builder $query): Builder => $query->whereKey($divisionSchool->getKey()))
                                    ->count()
                            )
                            ->query(fn (Builder $query): Builder => $query->whereHas(
                                'schools',
                                fn (Builder $query): Builder => $query->whereKey($divisionSchool->getKey()),
                            ));
                    });
            });

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected static function schoolPanelUsersQuery(Builder $query, mixed $schoolId): Builder
    {
        return $query->where(function (Builder $query) use ($schoolId): void {
            $query
                ->whereHas('schools', fn (Builder $query) => $query->whereKey($schoolId))
                ->orWhereHas('guardians', fn (Builder $query) => $query
                    ->where('school_id', $schoolId)
                    ->whereHas('studentLinks.student'));
        });
    }

    protected static function parentUsersQuery(Builder $query, mixed $schoolId): Builder
    {
        return $query->where(function (Builder $query) use ($schoolId): void {
            $query
                ->whereHas('schools', fn (Builder $query) => $query->whereKey($schoolId)->where('school_user.role', User::SCHOOL_ROLE_PARENT))
                ->orWhereHas('guardians', fn (Builder $query) => $query
                    ->where('school_id', $schoolId)
                    ->whereHas('studentLinks.student'));
        });
    }

    /**
     * @param  array<int, string>|string  $roles
     */
    protected static function schoolRoleUsersQuery(Builder $query, mixed $schoolId, array|string $roles): Builder
    {
        return $query->whereHas('schools', fn (Builder $query) => $query
            ->whereKey($schoolId)
            ->whereIn('school_user.role', (array) $roles));
    }

    protected static function superadminUsersQuery(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->where('role', User::ROLE_SUPERADMIN)
            ->orWhere('is_platform_admin', true));
    }
}
