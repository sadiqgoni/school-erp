<?php

namespace App\Filament\Resources\Enrollments\Tables;

use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Support\StudentPromotion;
use App\Support\TeacherWorkspace;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('student.admission_number')
                    ->label('Admission no.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(['student.last_name']),
                TextColumn::make('academicYear.name')
                    ->label('Academic year')
                    ->sortable(),
                TextColumn::make('schoolClass.name')
                    ->label('Class')
                    ->sortable(),
                TextColumn::make('classSection.name')
                    ->label('Arm')
                    ->placeholder('None'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'promoted', 'completed' => 'info',
                        'repeated' => 'warning',
                        'withdrawn' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('enrolled_on')
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
                SelectFilter::make('academic_year_id')
                    ->label('Academic year')
                    ->relationship('academicYear', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('school_class_id')
                    ->label('Class')
                    ->relationship('schoolClass', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'promoted' => 'Promoted',
                        'repeated' => 'Repeated',
                        'withdrawn' => 'Withdrawn',
                        'completed' => 'Completed',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                self::promotionBulkAction(),
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => ! TeacherWorkspace::isTeacher()),
                ]),
            ]);
    }

    protected static function promotionBulkAction(): BulkAction
    {
        return BulkAction::make('promoteStudents')
            ->label('Promote / Repeat / Graduate')
            ->icon('heroicon-o-academic-cap')
            ->color('success')
            ->modalHeading('Move selected students')
            ->modalDescription('Select the outcome for the students you ticked. You can promote different groups to different arms by running this action more than once.')
            ->modalSubmitActionLabel('Apply to selected students')
            ->schema([
                Select::make('outcome')
                    ->label('What is happening?')
                    ->options(StudentPromotion::OUTCOMES)
                    ->default(StudentPromotion::OUTCOME_PROMOTED)
                    ->live()
                    ->required(),
                Select::make('target_academic_year_id')
                    ->label('Into which session?')
                    ->options(fn (): array => AcademicYear::query()
                        ->orderByDesc('starts_on')
                        ->pluck('name', 'id')
                        ->all())
                    ->live()
                    ->visible(fn (Get $get): bool => $get('outcome') !== StudentPromotion::OUTCOME_GRADUATED)
                    ->requiredUnless('outcome', StudentPromotion::OUTCOME_GRADUATED)
                    ->helperText('Create the new academic session first if it does not exist yet.'),
                Select::make('target_term_id')
                    ->label('Term')
                    ->options(fn (Get $get): array => Term::query()
                        ->where('academic_year_id', $get('target_academic_year_id') ?: 0)
                        ->orderBy('position')
                        ->pluck('name', 'id')
                        ->all())
                    ->visible(fn (Get $get): bool => $get('outcome') !== StudentPromotion::OUTCOME_GRADUATED),
                Select::make('target_class_id')
                    ->label('New class')
                    ->options(fn (): array => SchoolClass::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->visible(fn (Get $get): bool => $get('outcome') === StudentPromotion::OUTCOME_PROMOTED)
                    ->requiredIf('outcome', StudentPromotion::OUTCOME_PROMOTED),
                Select::make('target_section_id')
                    ->label('New arm (optional)')
                    ->options(fn (Get $get): array => ClassSection::query()
                        ->where('school_class_id', $get('target_class_id') ?: 0)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->placeholder('No arm')
                    ->visible(fn (Get $get): bool => $get('outcome') === StudentPromotion::OUTCOME_PROMOTED),
            ])
            ->action(function (Collection $records, array $data): void {
                $result = StudentPromotion::apply($records, $data);

                $summary = match ($data['outcome']) {
                    StudentPromotion::OUTCOME_GRADUATED => $result['moved'].' student(s) graduated',
                    StudentPromotion::OUTCOME_REPEATED => $result['moved'].' student(s) set to repeat',
                    default => $result['moved'].' student(s) promoted',
                };

                Notification::make()
                    ->title($summary)
                    ->body($result['skipped'] > 0
                        ? $result['skipped'].' skipped (not active, or already placed in the target session).'
                        : null)
                    ->status($result['moved'] > 0 ? 'success' : 'warning')
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
