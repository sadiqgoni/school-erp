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
use Filament\Tables\Columns\IconColumn;
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
                ? $query->whereHas('schools', fn (Builder $query) => $query->whereKey(Filament::getTenant()?->getKey()))
                : $query)
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('school_role')
                    ->label('Role')
                    ->state(fn (User $record): string => match ($record->roleForSchool(Filament::getTenant())) {
                        'school_admin' => 'School admin',
                        'teacher' => 'Teacher',
                        'staff' => 'Staff',
                        default => 'Not assigned',
                    })
                    ->badge()
                    ->color(fn (User $record): string => match ($record->roleForSchool(Filament::getTenant())) {
                        'school_admin' => 'success',
                        'teacher' => 'info',
                        'staff' => 'gray',
                        default => 'warning',
                    })
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school'),
                TextColumn::make('schools.name')
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
                IconColumn::make('is_platform_admin')
                    ->boolean()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_platform_admin')
                    ->label('Platform admin'),
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
                        'role' => $record->roleForSchool(Filament::getTenant()) ?? 'staff',
                    ])
                    ->schema([
                        Select::make('role')
                            ->options([
                                'school_admin' => 'School admin',
                                'teacher' => 'Teacher',
                                'staff' => 'Staff',
                            ])
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $tenant = Filament::getTenant();

                        if (! $tenant) {
                            return;
                        }

                        $record->schools()->updateExistingPivot($tenant->getKey(), [
                            'role' => $data['role'],
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
}
