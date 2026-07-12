<?php

namespace App\Filament\Resources\Assignments\Schemas;

use App\Filament\Support\SchoolSelect;
use App\Models\ClassSection;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Support\TeacherWorkspace;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Assignment details')
                    ->description('Parents of the selected class will see this assignment on their portal.')
                    ->schema([
                        SchoolSelect::make(),
                        TextInput::make('title')
                            ->label('Assignment title')
                            ->placeholder('e.g. Mathematics workbook page 24–26')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('school_class_id')
                            ->label('Class')
                            ->options(fn (): array => self::classOptions())
                            ->default(fn (): ?int => TeacherWorkspace::lockedFormClassId())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->disabled(fn (): bool => TeacherWorkspace::shouldLockToFormAssignment())
                            ->dehydrated()
                            ->helperText(fn (): ?string => TeacherWorkspace::shouldLockToFormAssignment()
                                ? 'Locked to your assigned form class.'
                                : null)
                            ->required(),
                        Select::make('class_section_id')
                            ->label('Arm (optional)')
                            ->default(fn (): ?int => TeacherWorkspace::lockedFormSectionId())
                            ->options(fn (Get $get): array => self::sectionOptions((int) ($get('school_class_id') ?: 0)))
                            ->placeholder('Whole class')
                            ->searchable()
                            ->disabled(fn (): bool => TeacherWorkspace::shouldLockToFormAssignment())
                            ->dehydrated()
                            ->helperText(fn (): ?string => TeacherWorkspace::shouldLockToFormAssignment()
                                ? 'Locked to your assigned arm.'
                                : null),
                        Select::make('subject_id')
                            ->label('Subject (optional)')
                            ->options(fn (): array => Subject::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->placeholder('General / class work')
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->options([
                                'published' => 'Published (parents can see it)',
                                'draft' => 'Draft (hidden from parents)',
                            ])
                            ->default('published')
                            ->required(),
                        DatePicker::make('assigned_on')
                            ->label('Given on')
                            ->default(today())
                            ->required(),
                        DatePicker::make('due_on')
                            ->label('Due date')
                            ->afterOrEqual('assigned_on'),
                        Textarea::make('instructions')
                            ->label('Instructions for parents and students')
                            ->placeholder('Explain exactly what the student should do at home…')
                            ->rows(6)
                            ->columnSpanFull(),
                        FileUpload::make('attachment_path')
                            ->label('Attachment (optional)')
                            ->helperText('Attach a worksheet, picture, or PDF if there is one.')
                            ->disk('public')
                            ->directory('assignments')
                            ->visibility('public')
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(10240)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    protected static function classOptions(): array
    {
        $query = SchoolClass::query()->where('is_active', true)->orderBy('name');

        if (TeacherWorkspace::isTeacher()) {
            $query->whereKey(TeacherWorkspace::teachableClassIds() ?: [0]);
        }

        return $query->pluck('name', 'id')->all();
    }

    protected static function sectionOptions(int $classId): array
    {
        $query = ClassSection::query()->where('school_class_id', $classId)->orderBy('name');

        if (TeacherWorkspace::isTeacher()) {
            $allowedSectionIds = TeacherWorkspace::teachableSectionIds($classId);

            if ($allowedSectionIds !== null) {
                $query->whereKey($allowedSectionIds ?: [0]);
            }
        }

        return $query->pluck('name', 'id')->all();
    }
}
