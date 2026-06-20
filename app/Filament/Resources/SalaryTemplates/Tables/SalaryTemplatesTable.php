<?php

namespace App\Filament\Resources\SalaryTemplates\Tables;

use App\Models\SalaryTemplate;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SalaryTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query
                    ->selectRaw('MIN(id) as id')
                    ->selectRaw('school_id')
                    ->selectRaw('grade_level')
                    ->selectRaw("MAX(CASE WHEN step = '01' THEN monthly_basic END) as step1_monthly")
                    ->selectRaw("MAX(CASE WHEN step = '02' THEN monthly_basic END) as step2_monthly")
                    ->selectRaw("MAX(CASE WHEN step = '03' THEN monthly_basic END) as step3_monthly")
                    ->selectRaw("MAX(CASE WHEN step = '04' THEN monthly_basic END) as step4_monthly")
                    ->selectRaw("MAX(CASE WHEN step = '05' THEN monthly_basic END) as step5_monthly")
                    ->selectRaw("MAX(CASE WHEN step = '06' THEN monthly_basic END) as step6_monthly")
                    ->selectRaw("MAX(CASE WHEN step = '07' THEN monthly_basic END) as step7_monthly")
                    ->selectRaw("MAX(CASE WHEN step = '08' THEN monthly_basic END) as step8_monthly")
                    ->selectRaw("MAX(CASE WHEN step = '09' THEN monthly_basic END) as step9_monthly")
                    ->selectRaw("MAX(CASE WHEN step = '10' THEN monthly_basic END) as step10_monthly")
                    ->selectRaw('MAX(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as is_active')
                    ->groupBy('school_id', 'grade_level');
            })
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('grade_level')
                    ->label('Grade Level')
                    ->sortable()
                    ->weight('semibold')
                    ->searchable(),
                TextColumn::make('step1_monthly')
                    ->label('Step 1')
                    ->money('NGN')
                    ->alignEnd(),
                TextColumn::make('step2_monthly')
                    ->label('Step 2')
                    ->money('NGN')
                    ->alignEnd(),
                TextColumn::make('step3_monthly')
                    ->label('Step 3')
                    ->money('NGN')
                    ->alignEnd(),
                TextColumn::make('step4_monthly')
                    ->label('Step 4')
                    ->money('NGN')
                    ->alignEnd(),
                TextColumn::make('step5_monthly')
                    ->label('Step 5')
                    ->money('NGN')
                    ->alignEnd(),
                TextColumn::make('step6_monthly')
                    ->label('Step 6')
                    ->money('NGN')
                    ->alignEnd(),
                TextColumn::make('step7_monthly')
                    ->label('Step 7')
                    ->money('NGN')
                    ->alignEnd(),
                TextColumn::make('step8_monthly')
                    ->label('Step 8')
                    ->money('NGN')
                    ->alignEnd(),
                TextColumn::make('step9_monthly')
                    ->label('Step 9')
                    ->money('NGN')
                    ->alignEnd(),
                TextColumn::make('step10_monthly')
                    ->label('Step 10')
                    ->money('NGN')
                    ->alignEnd(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('deleteSelected')
                        ->label('Delete')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->groupBy('school_id')->each(function (Collection $schoolRows, mixed $schoolId): void {
                                SalaryTemplate::query()
                                    ->where('school_id', $schoolId)
                                    ->whereIn('grade_level', $schoolRows->pluck('grade_level')->filter()->all())
                                    ->delete();
                            });
                        }),
                ]),
            ])
            ->defaultSort('grade_level')
            ->striped();
    }
}
