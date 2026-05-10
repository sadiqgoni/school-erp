<?php

namespace App\Filament\Resources\Staff\Tables;

use App\Filament\Resources\TeachingAssignments\TeachingAssignmentResource;
use App\Models\Staff;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StaffTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->disk('public')
                    ->visibility('public')
                    ->defaultImageUrl(asset('images/branding/school-dice-logo-icon.png'))
                    ->circular(),
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('full_name')
                    ->label('Staff')
                    ->searchable(query: function ($query, string $search) {
                        return $query
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('middle_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('staff_number', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->sortable(['last_name'])
                    ->weight('semibold')
                    ->description(fn (Staff $record): string => collect([$record->staff_number, $record->email])->filter()->implode('  ·  ')),
                TextColumn::make('staff_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === Staff::TYPE_TEACHING ? 'Teaching' : 'Non-teaching')
                    ->color(fn (string $state): string => $state === Staff::TYPE_TEACHING ? 'success' : 'gray'),
                TextColumn::make('department.name')
                    ->label('Unit')
                    ->placeholder('Not set')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Staff $record): ?string => $record->job_title),
                TextColumn::make('highest_qualification')
                    ->label('Qualification')
                    ->formatStateUsing(fn (?string $state): ?string => $state ? (Staff::QUALIFICATION_OPTIONS[$state] ?? $state) : null)
                    ->placeholder('Not set')
                    ->toggleable(),
                TextColumn::make('employment_type')->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'on_leave' => 'info',
                        'suspended' => 'warning',
                        'resigned', 'terminated' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('staff_type')
                    ->label('Staff type')
                    ->options([
                        Staff::TYPE_TEACHING => 'Teaching staff',
                        Staff::TYPE_NON_TEACHING => 'Non-teaching staff',
                    ]),
                SelectFilter::make('department_id')
                    ->label('Department / Unit')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'on_leave' => 'On leave',
                        'suspended' => 'Suspended',
                        'resigned' => 'Resigned',
                        'terminated' => 'Terminated',
                    ]),
            ])
            ->recordActions([
                Action::make('assignTeaching')
                    ->label('Assign Teaching')
                    ->icon('heroicon-o-academic-cap')
                    ->color('primary')
                    ->visible(fn (Staff $record): bool => $record->staff_type === Staff::TYPE_TEACHING)
                    ->url(fn (Staff $record): string => TeachingAssignmentResource::getUrl('create', [
                        'tenant' => Filament::getTenant(),
                        'staff_id' => $record->getKey(),
                    ])),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
