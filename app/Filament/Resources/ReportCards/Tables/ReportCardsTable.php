<?php

namespace App\Filament\Resources\ReportCards\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReportCardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable(query: fn ($query, string $search) => $query->whereHas(
                        'student',
                        fn ($query) => $query
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('middle_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('admission_number', 'like', "%{$search}%"),
                    ))
                    ->weight('semibold')
                    ->description(fn ($record): ?string => $record->student?->admission_number),
                TextColumn::make('exam.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_score')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('average_score')
                    ->label('Average')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('position')
                    ->label('Class pos.')
                    ->sortable(),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'published' => 'success',
                    'approved' => 'info',
                    default => 'gray',
                }),
            ])
            ->filters([
                SelectFilter::make('school')->relationship('school', 'name')->searchable()->preload(),
                SelectFilter::make('exam_id')->label('Exam')->relationship('exam', 'name')->searchable()->preload(),
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'approved' => 'Approved',
                    'published' => 'Published',
                ]),
            ])
            ->recordActions([
                Action::make('downloadPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record): string => route('report-cards.pdf', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('No report cards prepared yet')
            ->emptyStateDescription('Compile results first, then review comments and publish report cards.')
            ->striped();
    }
}
