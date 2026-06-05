<?php

namespace App\Filament\Resources\ClassSubjects\Pages;

use App\Filament\Resources\ClassSubjects\ClassSubjectResource;
use App\Filament\Support\ClassTabs;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Subject;
use App\Support\TeacherWorkspace;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListClassSubjects extends ListRecords
{
    protected static string $resource = ClassSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assignSubjects')
                ->label('Assign to classes')
                ->icon('heroicon-o-link')
                ->color('primary')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school' && (! TeacherWorkspace::isTeacher() || filled(TeacherWorkspace::formClassIds())))
                ->modalHeading(fn (): string => TeacherWorkspace::isTeacher() ? 'Assign subjects to my class' : 'Assign subjects to classes')
                ->modalDescription(fn (): string => TeacherWorkspace::isTeacher() ? 'Select from the classes where you are the form teacher.' : 'Select classes and subjects to create class subject entries in bulk.')
                ->modalWidth('5xl')
                ->schema([
                    CheckboxList::make('school_class_ids')
                        ->label('Classes')
                        ->options(fn (): array => SchoolClass::query()
                            ->when(TeacherWorkspace::isTeacher(), fn ($query) => $query->whereIn('id', TeacherWorkspace::formClassIds()))
                            ->orderBy('level')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->columns(2)
                        ->required(),
                    CheckboxList::make('subject_ids')
                        ->label('Subjects')
                        ->options(fn (): array => Subject::query()->orderBy('department')->orderBy('name')->pluck('name', 'id')->all())
                        ->columns(2)
                        ->required(),
                    Select::make('staff_id')
                        ->label('Subject teacher')
                        ->options(fn (): array => Staff::query()
                            ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                            ->where('staff_type', Staff::TYPE_TEACHING)
                            ->orderBy('last_name')
                            ->orderBy('first_name')
                            ->get()
                            ->mapWithKeys(fn (Staff $staff): array => [$staff->getKey() => "{$staff->full_name} ({$staff->staff_number})"])
                            ->all())
                        ->searchable()
                        ->preload(),
                    TextInput::make('weekly_periods')
                        ->numeric()
                        ->default(4)
                        ->minValue(1)
                        ->maxValue(20)
                        ->required(),
                    Toggle::make('is_compulsory')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        return;
                    }

                    $count = 0;

                    foreach (($data['school_class_ids'] ?? []) as $classId) {
                        foreach (($data['subject_ids'] ?? []) as $subjectId) {
                            ClassSubject::query()->updateOrCreate(
                                [
                                    'school_id' => $tenant->getKey(),
                                    'school_class_id' => $classId,
                                    'subject_id' => $subjectId,
                                ],
                                [
                                    'staff_id' => $data['staff_id'] ?: null,
                                    'weekly_periods' => $data['weekly_periods'],
                                    'is_compulsory' => (bool) ($data['is_compulsory'] ?? true),
                                    'is_active' => true,
                                ],
                            );

                            $count++;
                        }
                    }

                    Notification::make()
                        ->success()
                        ->title('Assignments saved')
                        ->body("Processed {$count} class subject assignments.")
                        ->send();
                }),
            CreateAction::make()
                ->visible(fn (): bool => ! TeacherWorkspace::isTeacher()),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::direct(ClassSubject::class, 'All class subjects');
    }
}
