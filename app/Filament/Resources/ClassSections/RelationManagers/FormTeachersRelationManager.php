<?php

namespace App\Filament\Resources\ClassSections\RelationManagers;

use App\Models\AcademicYear;
use App\Models\Staff;
use App\Models\TeachingAssignment;
use App\Models\Term;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FormTeachersRelationManager extends RelationManager
{
    protected static string $relationship = 'teachingAssignments';

    protected static ?string $title = 'Form Teachers';

    public function form(Schema $schema): Schema
    {
        $arm = $this->getOwnerRecord();

        return $schema
            ->components([
                Select::make('staff_id')
                    ->label('Teacher')
                    ->options(fn (): array => Staff::query()
                        ->where('school_id', $arm->school_id)
                        ->where('staff_type', Staff::TYPE_TEACHING)
                        ->orderBy('last_name')
                        ->orderBy('first_name')
                        ->get()
                        ->mapWithKeys(fn (Staff $staff): array => [$staff->getKey() => "{$staff->full_name} ({$staff->staff_number})"])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('academic_year_id')
                    ->label('Academic year')
                    ->options(fn (): array => AcademicYear::query()
                        ->where('school_id', $arm->school_id)
                        ->orderByDesc('starts_on')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('term_id')
                    ->label('Term')
                    ->options(fn (): array => Term::query()
                        ->where('school_id', $arm->school_id)
                        ->orderBy('academic_year_id')
                        ->orderBy('position')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
                Select::make('assignment_role')
                    ->label('Role')
                    ->options([
                        TeachingAssignment::ROLE_FORM_TEACHER => 'Form teacher',
                        TeachingAssignment::ROLE_ASSISTANT_FORM_TEACHER => 'Assistant form teacher',
                    ])
                    ->default(TeachingAssignment::ROLE_FORM_TEACHER)
                    ->required(),
                Toggle::make('is_class_teacher')
                    ->label('Primary arm teacher')
                    ->default(true),
                Toggle::make('is_active')
                    ->default(true),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('assignment_role', [
                TeachingAssignment::ROLE_FORM_TEACHER,
                TeachingAssignment::ROLE_ASSISTANT_FORM_TEACHER,
            ]))
            ->recordTitleAttribute('staff_id')
            ->columns([
                TextColumn::make('staff.full_name')
                    ->label('Teacher')
                    ->searchable()
                    ->description(fn ($record): ?string => $record->staff?->staff_number),
                TextColumn::make('academicYear.name')
                    ->label('Academic year')
                    ->sortable(),
                TextColumn::make('term.name')
                    ->placeholder('All terms'),
                TextColumn::make('assignment_role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        TeachingAssignment::ROLE_FORM_TEACHER => 'Form teacher',
                        TeachingAssignment::ROLE_ASSISTANT_FORM_TEACHER => 'Assistant form teacher',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),
                IconColumn::make('is_class_teacher')
                    ->label('Primary')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('academic_year_id')
                    ->label('Academic year')
                    ->relationship('academicYear', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Assign form teacher')
                    ->mutateDataUsing(function (array $data): array {
                        $arm = $this->getOwnerRecord();

                        $data['school_id'] = $arm->school_id;
                        $data['school_class_id'] = $arm->school_class_id;
                        $data['class_section_id'] = $arm->getKey();
                        $data['subject_id'] = null;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $arm = $this->getOwnerRecord();

                        $data['school_id'] = $arm->school_id;
                        $data['school_class_id'] = $arm->school_class_id;
                        $data['class_section_id'] = $arm->getKey();
                        $data['subject_id'] = null;

                        return $data;
                    }),
                DeleteAction::make(),
            ]);
    }
}
