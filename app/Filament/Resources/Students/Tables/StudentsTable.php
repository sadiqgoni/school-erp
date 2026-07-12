<?php

namespace App\Filament\Resources\Students\Tables;

use App\Models\Student;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('')
                    ->disk('public')
                    ->visibility('public')
                    ->circular()
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=Student&background=E2E8F0&color=334155')
                    ->toggleable(),
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('admission_number')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('full_name')
                    ->label('Student')
                    ->searchable(['first_name', 'middle_name', 'last_name'])
                    ->sortable(['last_name'])
                    ->weight('semibold')
                    ->description(fn ($record): string => collect([
                        $record->gender ? ucfirst($record->gender) : null,
                        $record->date_of_birth?->format('d M Y'),
                    ])->filter()->implode('  ·  ')),
                TextColumn::make('age')
                    ->label('Age')
                    ->state(fn ($record): ?string => $record->date_of_birth ? $record->date_of_birth->age.' yrs' : null)
                    ->badge()
                    ->color('primary')
                    ->placeholder('Not set'),
                TextColumn::make('gender')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'male' => 'info',
                        'female' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('enrollments.schoolClass.name')
                    ->label('Class')
                    ->badge()
                    ->color('info')
                    ->separator(',')
                    ->placeholder('No class')
                    ->toggleable(),
                TextColumn::make('guardian_links_count')
                    ->counts('guardianLinks')
                    ->label('Contacts')
                    ->badge()
                    ->color('info')
                    ->toggleable(),
                TextColumn::make('enrollments_count')
                    ->counts('enrollments')
                    ->label('Placements')
                    ->badge()
                    ->color('success')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'warning',
                        'graduated' => 'info',
                        'transferred', 'inactive' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('admitted_on')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'graduated' => 'Graduated',
                        'transferred' => 'Transferred',
                        'suspended' => 'Suspended',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Student $record): bool => ! $record->trashed()),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_name')
            ->striped();
    }
}
