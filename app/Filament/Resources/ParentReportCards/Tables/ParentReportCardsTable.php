<?php

namespace App\Filament\Resources\ParentReportCards\Tables;

use App\Models\ReportCard;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ParentReportCardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => self::scopeToParent($query))
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn (ReportCard $record): ?string => $record->student?->admission_number),
                TextColumn::make('student.enrollments.schoolClass.name')
                    ->label('Class')
                    ->state(fn (ReportCard $record): string => self::placementLabel($record))
                    ->badge(),
                TextColumn::make('exam.name')
                    ->label('Exam')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('academicYear.name')
                    ->label('Session')
                    ->sortable(),
                TextColumn::make('term.name')
                    ->label('Term')
                    ->placeholder('Whole session')
                    ->sortable(),
                TextColumn::make('average_score')
                    ->label('Average')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('position')
                    ->label('Class pos.')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'approved' => 'info',
                        'form_teacher_reviewed' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()),
            ])
            ->filters([
                SelectFilter::make('student_id')
                    ->label('Child')
                    ->options(fn (): array => self::childOptions())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('academic_year_id')
                    ->label('Session')
                    ->relationship('academicYear', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('term_id')
                    ->label('Term')
                    ->relationship('term', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('downloadPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (ReportCard $record): string => route('report-cards.pdf', $record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No published results yet')
            ->emptyStateDescription('Report cards for your linked children will appear here after the school publishes them.')
            ->striped();
    }

    protected static function scopeToParent(Builder $query): Builder
    {
        $userId = Filament::auth()->id();
        $tenant = Filament::getTenant();

        return $query
            ->with(['student.enrollments.schoolClass', 'student.enrollments.classSection', 'exam', 'academicYear', 'term'])
            ->where('school_id', $tenant?->getKey())
            ->where('status', 'published')
            ->whereHas('student.guardianLinks.guardian', fn (Builder $query) => $query->where('user_id', $userId));
    }

    protected static function childOptions(): array
    {
        $userId = Filament::auth()->id();
        $tenant = Filament::getTenant();

        return \App\Models\Student::query()
            ->where('school_id', $tenant?->getKey())
            ->whereHas('guardianLinks.guardian', fn (Builder $query) => $query->where('user_id', $userId))
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(fn ($student): array => [$student->getKey() => $student->full_name])
            ->all();
    }

    protected static function placementLabel(ReportCard $record): string
    {
        $placement = $record->student
            ?->enrollments
            ->where('academic_year_id', $record->academic_year_id)
            ->when($record->term_id, fn ($enrollments) => $enrollments->where('term_id', $record->term_id))
            ->sortByDesc('enrolled_on')
            ->first()
            ?? $record->student?->enrollments->sortByDesc('enrolled_on')->first();

        return collect([
            $placement?->schoolClass?->name,
            $placement?->classSection?->name,
        ])->filter()->implode(' ') ?: 'Not set';
    }
}
