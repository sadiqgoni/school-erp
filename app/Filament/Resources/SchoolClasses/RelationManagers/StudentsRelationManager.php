<?php

namespace App\Filament\Resources\SchoolClasses\RelationManagers;

use App\Filament\Resources\Students\StudentResource;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Support\StudentClassPlacement;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Students';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('student_id')
            ->headerActions([
                Action::make('addStudents')
                    ->label('Add students')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->modalHeading(fn (): string => 'Add students to '.$this->getOwnerRecord()->name)
                    ->modalSubmitActionLabel('Save placements')
                    ->schema(function (): array {
                        /** @var SchoolClass $record */
                        $record = $this->getOwnerRecord();

                        return [
                            Select::make('academic_year_id')
                                ->label('Academic year')
                                ->options(fn (): array => AcademicYear::query()
                                    ->where('school_id', $record->school_id)
                                    ->orderByDesc('starts_on')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->default(fn (): ?int => AcademicYear::query()
                                    ->where('school_id', $record->school_id)
                                    ->where('is_current', true)
                                    ->value('id'))
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('term_id')
                                ->label('Term')
                                ->options(fn (): array => Term::query()
                                    ->where('school_id', $record->school_id)
                                    ->orderBy('academic_year_id')
                                    ->orderBy('position')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->default(fn (): ?int => Term::query()
                                    ->where('school_id', $record->school_id)
                                    ->where('is_current', true)
                                    ->value('id'))
                                ->searchable()
                                ->preload(),
                            Select::make('class_section_id')
                                ->label('Arm')
                                ->options(fn (): array => $record->sections()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->visible(fn (): bool => $record->sections()->exists())
                                ->searchable()
                                ->preload(),
                            Select::make('student_ids')
                                ->label('Students')
                                ->multiple()
                                ->options(fn (): array => Student::query()
                                    ->where('school_id', $record->school_id)
                                    ->where('status', 'active')
                                    ->orderBy('last_name')
                                    ->orderBy('first_name')
                                    ->get()
                                    ->mapWithKeys(fn (Student $student): array => [$student->getKey() => "{$student->full_name} ({$student->admission_number})"])
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpanFull(),
                            DatePicker::make('enrolled_on')
                                ->label('Placement date')
                                ->default(today()),
                            Select::make('status')
                                ->default('active')
                                ->options([
                                    'active' => 'Active',
                                    'completed' => 'Completed',
                                    'transferred' => 'Transferred',
                                    'withdrawn' => 'Withdrawn',
                                ])
                                ->required(),
                            Textarea::make('remarks')
                                ->columnSpanFull(),
                        ];
                    })
                    ->action(function (array $data): void {
                        /** @var SchoolClass $record */
                        $record = $this->getOwnerRecord();
                        $saved = app(StudentClassPlacement::class)->placeStudents($record, $data);

                        Notification::make()
                            ->success()
                            ->title('Students added')
                            ->body("Saved {$saved} student placement(s) for {$record->name}.")
                            ->send();
                    }),
            ])
            ->columns([
                ImageColumn::make('student.photo_path')
                    ->label('')
                    ->disk('public')
                    ->visibility('public')
                    ->circular()
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=Student&background=E2E8F0&color=334155')
                    ->toggleable(),
                TextColumn::make('student.admission_number')
                    ->label('Admission no.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable()
                    ->description(fn ($record): string => collect([
                        $record->student?->gender ? ucfirst($record->student->gender) : null,
                        $record->student?->date_of_birth?->format('d M Y'),
                    ])->filter()->implode('  ·  ')),
                TextColumn::make('classSection.name')
                    ->label('Arm')
                    ->placeholder('No arm')
                    ->sortable(),
                TextColumn::make('academicYear.name')
                    ->label('Session')
                    ->sortable(),
                TextColumn::make('term.name')
                    ->placeholder('All terms')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'transferred', 'withdrawn' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('enrolled_on')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'active' => 'Active',
                    'completed' => 'Completed',
                    'transferred' => 'Transferred',
                    'withdrawn' => 'Withdrawn',
                ]),
                SelectFilter::make('class_section_id')
                    ->label('Arm')
                    ->relationship('classSection', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('viewStudent')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record): string => StudentResource::getUrl('view', ['record' => $record->student_id]))
                    ->openUrlInNewTab(false),
            ]);
    }
}
