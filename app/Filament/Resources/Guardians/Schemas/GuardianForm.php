<?php

namespace App\Filament\Resources\Guardians\Schemas;

use App\Filament\Support\SchoolSelect;
use App\Models\Student;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GuardianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Parent / Guardian')
                    ->schema([
                        SchoolSelect::make(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(30),
                        TextInput::make('alternate_phone')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('occupation')
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->default(true),
                        Textarea::make('address')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Related Students')
                    ->schema([
                        Repeater::make('student_links')
                            ->label('Students under this parent / guardian')
                            ->defaultItems(0)
                            ->maxItems(6)
                            ->schema([
                                Select::make('student_id')
                                    ->label('Student')
                                    ->options(fn (): array => Student::query()
                                        ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                        ->orderBy('last_name')
                                        ->orderBy('first_name')
                                        ->get()
                                        ->mapWithKeys(fn (Student $student): array => [$student->getKey() => "{$student->full_name} ({$student->admission_number})"])
                                        ->all())
                                    ->searchable()
                                    ->required(),
                                Select::make('relationship')
                                    ->required()
                                    ->default('guardian')
                                    ->options([
                                        'father' => 'Father',
                                        'mother' => 'Mother',
                                        'guardian' => 'Guardian',
                                        'uncle' => 'Uncle',
                                        'aunt' => 'Aunt',
                                        'sibling' => 'Sibling',
                                        'other' => 'Other',
                                    ]),
                                Toggle::make('is_primary_contact')
                                    ->label('Primary contact'),
                                Toggle::make('can_pick_up')
                                    ->default(true),
                                Toggle::make('receives_sms')
                                    ->default(true),
                                Textarea::make('notes')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Link student')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['student_id'] ? 'Student link' : null),
                    ]),
            ]);
    }
}
