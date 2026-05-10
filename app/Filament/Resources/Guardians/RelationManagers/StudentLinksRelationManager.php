<?php

namespace App\Filament\Resources\Guardians\RelationManagers;

use App\Models\Student;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentLinksRelationManager extends RelationManager
{
    protected static string $relationship = 'studentLinks';

    protected static ?string $title = 'Related Students';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_id')
                    ->label('Student')
                    ->options(fn (): array => Student::query()
                        ->where('school_id', $this->getOwnerRecord()->school_id)
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
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('student_id')
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable()
                    ->description(fn ($record): ?string => $record->student?->admission_number),
                TextColumn::make('relationship')
                    ->badge(),
                IconColumn::make('is_primary_contact')
                    ->boolean()
                    ->label('Primary'),
                IconColumn::make('receives_sms')
                    ->boolean()
                    ->label('SMS'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->recordActions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
