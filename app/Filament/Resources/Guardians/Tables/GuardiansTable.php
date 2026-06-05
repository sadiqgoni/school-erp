<?php

namespace App\Filament\Resources\Guardians\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class GuardiansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('user.email')
                    ->label('Login')
                    ->badge()
                    ->placeholder('No login')
                    ->toggleable(),
                TextColumn::make('occupation')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
            ])
            ->recordActions([
                Action::make('createParentLogin')
                    ->label(fn ($record): string => $record->user_id ? 'Sync login' : 'Create login')
                    ->icon('heroicon-o-key')
                    ->color('success')
                    ->visible(fn ($record): bool => filled($record->email))
                    ->action(function ($record): void {
                        $user = User::query()->firstOrCreate(
                            ['email' => $record->email],
                            [
                                'name' => $record->name,
                                'password' => Hash::make('password'),
                                'is_platform_admin' => false,
                                'is_active' => true,
                            ],
                        );

                        $record->forceFill(['user_id' => $user->getKey()])->save();

                        $user->schools()->syncWithoutDetaching([
                            $record->school_id => [
                                'role' => 'parent',
                                'is_primary' => false,
                            ],
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Parent login ready')
                            ->body("Email: {$user->email}. Temporary password: password.")
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
