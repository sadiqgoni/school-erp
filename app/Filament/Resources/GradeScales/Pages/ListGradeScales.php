<?php

namespace App\Filament\Resources\GradeScales\Pages;

use App\Filament\Resources\GradeScales\GradeScaleResource;
use App\Models\GradeScale;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListGradeScales extends ListRecords
{
    protected static string $resource = GradeScaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('quickSetup')
                ->label('Quick Setup')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->modalHeading('Generate grade scale')
                ->modalDescription('Create a standard grade scale now. You can edit any grade, score range, point, or remark afterward.')
                ->modalSubmitActionLabel('Generate scale')
                ->schema([
                    Select::make('preset')
                        ->label('Preset')
                        ->options([
                            'standard' => 'Standard A–F (70/60/50/45/40)',
                            'waec' => 'WAEC / Senior Secondary A1–F9',
                            'primary' => 'Primary & Nursery A–E (with friendly remarks)',
                        ])
                        ->default('standard')
                        ->required()
                        ->helperText('Pick the scale that matches this division — e.g. WAEC style for secondary, A–E for primary.'),
                    TextInput::make('name')
                        ->label('Scale name')
                        ->default('Default')
                        ->required()
                        ->maxLength(255),
                    Checkbox::make('overwrite')
                        ->label('Overwrite existing grades with the same scale name')
                        ->helperText('Leave unchecked if you have already edited the scale manually.'),
                ])
                ->action(function (array $data): void {
                    $schoolId = Filament::getTenant()?->getKey();

                    if (! $schoolId) {
                        return;
                    }

                    $grades = match ($data['preset'] ?? 'standard') {
                        'waec' => [
                            ['grade' => 'A1', 'min_score' => 75, 'max_score' => 100, 'grade_point' => 9, 'remark' => 'Excellent'],
                            ['grade' => 'B2', 'min_score' => 70, 'max_score' => 74.99, 'grade_point' => 8, 'remark' => 'Very Good'],
                            ['grade' => 'B3', 'min_score' => 65, 'max_score' => 69.99, 'grade_point' => 7, 'remark' => 'Good'],
                            ['grade' => 'C4', 'min_score' => 60, 'max_score' => 64.99, 'grade_point' => 6, 'remark' => 'Credit'],
                            ['grade' => 'C5', 'min_score' => 55, 'max_score' => 59.99, 'grade_point' => 5, 'remark' => 'Credit'],
                            ['grade' => 'C6', 'min_score' => 50, 'max_score' => 54.99, 'grade_point' => 4, 'remark' => 'Credit'],
                            ['grade' => 'D7', 'min_score' => 45, 'max_score' => 49.99, 'grade_point' => 3, 'remark' => 'Pass'],
                            ['grade' => 'E8', 'min_score' => 40, 'max_score' => 44.99, 'grade_point' => 2, 'remark' => 'Pass'],
                            ['grade' => 'F9', 'min_score' => 0, 'max_score' => 39.99, 'grade_point' => 1, 'remark' => 'Fail'],
                        ],
                        'primary' => [
                            ['grade' => 'A', 'min_score' => 80, 'max_score' => 100, 'grade_point' => 5, 'remark' => 'Excellent'],
                            ['grade' => 'B', 'min_score' => 70, 'max_score' => 79.99, 'grade_point' => 4, 'remark' => 'Very Good'],
                            ['grade' => 'C', 'min_score' => 60, 'max_score' => 69.99, 'grade_point' => 3, 'remark' => 'Good'],
                            ['grade' => 'D', 'min_score' => 50, 'max_score' => 59.99, 'grade_point' => 2, 'remark' => 'Needs Improvement'],
                            ['grade' => 'E', 'min_score' => 0, 'max_score' => 49.99, 'grade_point' => 1, 'remark' => 'Needs Support'],
                        ],
                        default => [
                            ['grade' => 'A', 'min_score' => 70, 'max_score' => 100, 'grade_point' => 5, 'remark' => 'Excellent'],
                            ['grade' => 'B', 'min_score' => 60, 'max_score' => 69.99, 'grade_point' => 4, 'remark' => 'Very Good'],
                            ['grade' => 'C', 'min_score' => 50, 'max_score' => 59.99, 'grade_point' => 3, 'remark' => 'Good'],
                            ['grade' => 'D', 'min_score' => 45, 'max_score' => 49.99, 'grade_point' => 2, 'remark' => 'Fair'],
                            ['grade' => 'E', 'min_score' => 40, 'max_score' => 44.99, 'grade_point' => 1, 'remark' => 'Pass'],
                            ['grade' => 'F', 'min_score' => 0, 'max_score' => 39.99, 'grade_point' => 0, 'remark' => 'Fail'],
                        ],
                    };

                    $created = 0;
                    $updated = 0;
                    $skipped = 0;

                    foreach ($grades as $grade) {
                        $existing = GradeScale::query()
                            ->where('school_id', $schoolId)
                            ->where('name', $data['name'])
                            ->where('grade', $grade['grade'])
                            ->first();

                        if ($existing && ! ($data['overwrite'] ?? false)) {
                            $skipped++;

                            continue;
                        }

                        GradeScale::query()->updateOrCreate(
                            [
                                'school_id' => $schoolId,
                                'name' => $data['name'],
                                'grade' => $grade['grade'],
                            ],
                            [
                                ...$grade,
                                'is_active' => true,
                            ],
                        );

                        $existing ? $updated++ : $created++;
                    }

                    Notification::make()
                        ->title('Grade scale ready')
                        ->body("Created {$created}, updated {$updated}, skipped {$skipped}.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
