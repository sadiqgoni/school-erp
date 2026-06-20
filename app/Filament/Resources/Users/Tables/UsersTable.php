<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => Filament::getCurrentPanel()?->getId() === 'school'
                ? self::schoolPanelUsersQuery($query)
                : $query->with('schools'))
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('school_role')
                    ->label('Role')
                    ->state(fn (User $record): string => self::roleLabel($record))
                    ->badge()
                    ->color(fn (User $record): string => self::roleColor($record)),
                TextColumn::make('school_names')
                    ->label('School')
                    ->state(fn (User $record): string => self::schoolNames($record))
                    ->badge()
                    ->toggleable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                Action::make('resetPassword')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->schema([
                        TextInput::make('password')
                            ->label('New password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->forceFill([
                            'password' => Hash::make($data['password']),
                        ])->save();

                        Notification::make()
                            ->title('Password reset')
                            ->body("{$record->name} can now sign in with the new password.")
                            ->success()
                            ->send();
                    }),
                Action::make('changeRole')
                    ->label('Change Role')
                    ->icon('heroicon-o-user-circle')
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                    ->fillForm(fn (User $record): array => [
                        'role' => $record->roleForSchool(Filament::getTenant()) ?? User::SCHOOL_ROLE_STAFF,
                    ])
                    ->schema([
                        Select::make('role')
                            ->options([
                                User::SCHOOL_ROLE_ADMIN => 'Admin',
                                User::SCHOOL_ROLE_FINANCE => 'Finance',
                                User::SCHOOL_ROLE_TEACHER => 'Teacher',
                                User::SCHOOL_ROLE_STAFF => 'Staff',
                                User::SCHOOL_ROLE_PARENT => 'Parent',
                            ])
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $tenant = Filament::getTenant();

                        if (! $tenant) {
                            return;
                        }

                        $record->schools()->syncWithoutDetaching([
                            $tenant->getKey() => [
                                'role' => $data['role'],
                                'is_primary' => false,
                            ],
                        ]);

                        Notification::make()
                            ->title('User role updated')
                            ->success()
                            ->send();
                    }),
                ViewAction::make()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
            ]);
    }

    protected static function schoolPanelUsersQuery(Builder $query): Builder
    {
        $tenant = Filament::getTenant();

        return $query->where(function (Builder $query) use ($tenant): void {
            $query
                ->whereHas('schools', fn (Builder $query) => $query->whereKey($tenant?->getKey()))
                ->orWhereHas('guardians', fn (Builder $query) => $query
                    ->where('school_id', $tenant?->getKey())
                    ->whereHas('studentLinks.student'));
        });
    }

    protected static function roleLabel(User $user): string
    {
        if (Filament::getCurrentPanel()?->getId() === 'admin' && $user->isSuperAdmin()) {
            return 'Superadmin';
        }

        return match (self::roleForTenant($user)) {
            User::SCHOOL_ROLE_ADMIN => 'Admin',
            User::SCHOOL_ROLE_FINANCE => 'Finance',
            User::SCHOOL_ROLE_TEACHER => 'Teacher',
            User::SCHOOL_ROLE_STAFF => 'Staff',
            User::SCHOOL_ROLE_PARENT => 'Parent',
            User::ROLE_SUPERADMIN => 'Superadmin',
            default => 'Not assigned',
        };
    }

    protected static function roleColor(User $user): string
    {
        if (Filament::getCurrentPanel()?->getId() === 'admin' && $user->isSuperAdmin()) {
            return 'danger';
        }

        return match (self::roleForTenant($user)) {
            User::SCHOOL_ROLE_ADMIN => 'success',
            User::SCHOOL_ROLE_FINANCE => 'primary',
            User::SCHOOL_ROLE_TEACHER => 'info',
            User::SCHOOL_ROLE_PARENT => 'warning',
            User::SCHOOL_ROLE_STAFF => 'gray',
            User::ROLE_SUPERADMIN => 'danger',
            default => 'danger',
        };
    }

    protected static function roleForTenant(User $user): ?string
    {
        $tenant = Filament::getTenant();

        if (Filament::getCurrentPanel()?->getId() === 'admin') {
            return User::normalizeSchoolRole(
                $user->schools->pluck('pivot.role')->filter()->first()
            );
        }

        $role = $user->roleForSchool($tenant);

        if ($role) {
            return $role;
        }

        return $user->guardians()
            ->where('school_id', $tenant?->getKey())
            ->whereHas('studentLinks.student')
            ->exists()
                ? 'parent'
                : null;
    }

    protected static function schoolNames(User $user): string
    {
        return $user->schools
            ->map(fn ($school): string => $school->baseSchoolName())
            ->unique()
            ->values()
            ->implode(', ') ?: 'Not assigned';
    }
}
