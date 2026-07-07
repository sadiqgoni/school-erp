<?php

namespace App\Filament\Resources\Notices\Schemas;

use App\Filament\Support\SchoolSelect;
use App\Models\ClassSection;
use App\Models\Notice;
use App\Models\SchoolClass;
use App\Support\TeacherWorkspace;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class NoticeForm
{
    public static function configure(Schema $schema): Schema
    {
        $isTeacher = TeacherWorkspace::isTeacher();

        return $schema
            ->components([
                Section::make($isTeacher ? 'Class announcement' : 'Notice / newsletter')
                    ->description($isTeacher
                        ? 'This announcement goes to the parents of your class only.'
                        : 'Type the notice, or upload the printed newsletter and choose who should see it.')
                    ->schema([
                        SchoolSelect::make(),
                        TextInput::make('title')
                            ->placeholder('e.g. PTA meeting this Saturday')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('category')
                            ->options([
                                'general' => 'General',
                                'event' => 'Event',
                                'fees' => 'Fees',
                                'exam' => 'Exams',
                                'newsletter' => 'Newsletter',
                                'urgent' => 'Urgent',
                            ])
                            ->default('general')
                            ->required(),
                        DatePicker::make('expires_on')
                            ->label('Show until (optional)')
                            ->helperText('After this date the notice disappears from the parent portal.'),
                        Radio::make('audience_type')
                            ->label('Who should see it?')
                            ->options($isTeacher
                                ? [Notice::AUDIENCE_CLASS => 'My class parents']
                                : [
                                    Notice::AUDIENCE_ALL => 'Everyone (whole school)',
                                    Notice::AUDIENCE_DIVISION => 'One division (e.g. Primary only)',
                                    Notice::AUDIENCE_CLASS => 'One class',
                                ])
                            ->default($isTeacher ? Notice::AUDIENCE_CLASS : Notice::AUDIENCE_ALL)
                            ->live()
                            ->required()
                            ->columnSpanFull(),
                        Select::make('audience_division')
                            ->label('Division')
                            ->options(fn (): array => self::divisionOptions())
                            ->visible(fn (Get $get): bool => $get('audience_type') === Notice::AUDIENCE_DIVISION)
                            ->requiredIf('audience_type', Notice::AUDIENCE_DIVISION),
                        Select::make('school_class_id')
                            ->label('Class')
                            ->options(fn (): array => self::classOptions())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn (Get $get): bool => $get('audience_type') === Notice::AUDIENCE_CLASS)
                            ->requiredIf('audience_type', Notice::AUDIENCE_CLASS),
                        Select::make('class_section_id')
                            ->label('Arm (optional)')
                            ->options(fn (Get $get): array => ClassSection::query()
                                ->where('school_class_id', $get('school_class_id') ?: 0)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->placeholder('Whole class')
                            ->visible(fn (Get $get): bool => $get('audience_type') === Notice::AUDIENCE_CLASS),
                        Textarea::make('body')
                            ->label('Message')
                            ->placeholder('Type the notice here…')
                            ->rows(7)
                            ->columnSpanFull(),
                        FileUpload::make('attachment_path')
                            ->label('Upload printed newsletter / flyer (optional)')
                            ->helperText('PDF or photo of the printed notice — parents can download it.')
                            ->disk('public')
                            ->directory('notices')
                            ->visibility('public')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(15360)
                            ->columnSpanFull(),
                        Toggle::make('is_pinned')
                            ->label('Pin to the top')
                            ->visible(! $isTeacher),
                        Select::make('status')
                            ->options([
                                Notice::STATUS_PUBLISHED => 'Published (visible now)',
                                Notice::STATUS_DRAFT => 'Draft (hidden)',
                            ])
                            ->default(Notice::STATUS_PUBLISHED)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    protected static function divisionOptions(): array
    {
        return SchoolClass::query()
            ->where('school_id', Filament::getTenant()?->getKey())
            ->where('is_active', true)
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department', 'department')
            ->all();
    }

    protected static function classOptions(): array
    {
        $query = SchoolClass::query()->where('is_active', true)->orderBy('name');

        if (TeacherWorkspace::isTeacher()) {
            $query->whereKey(TeacherWorkspace::formClassIds() ?: [0]);
        }

        return $query->pluck('name', 'id')->all();
    }
}
