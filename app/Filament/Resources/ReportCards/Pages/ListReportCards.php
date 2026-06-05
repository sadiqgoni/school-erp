<?php

namespace App\Filament\Resources\ReportCards\Pages;

use App\Filament\Resources\CompiledResults\Pages\ListCompiledResults;
use App\Filament\Resources\ReportCards\ReportCardResource;
use App\Filament\Support\ClassTabs;
use App\Models\Exam;
use App\Models\ReportCard;
use App\Support\TeacherWorkspace;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListReportCards extends ListRecords
{
    protected static string $resource = ReportCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('compileResults')
                ->label('Compile Results')
                ->icon('heroicon-o-calculator')
                ->color('primary')
                ->visible(fn (): bool => ! TeacherWorkspace::isTeacher())
                ->modalHeading('Compile student results')
                ->modalDescription('Use this after teachers have entered scores. It prepares each student result card for PDF download.')
                ->modalSubmitActionLabel('Compile results')
                ->schema([
                    Select::make('exam_id')
                        ->label('Exam')
                        ->options(fn (): array => Exam::query()
                            ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                            ->orderByDesc('created_at')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('status')
                        ->label('Score status to include')
                        ->default('submitted')
                        ->required()
                        ->options([
                            'submitted' => 'Submitted scores',
                            'approved' => 'Approved scores only',
                            'draft' => 'Draft, submitted, and approved scores',
                        ]),
                    Checkbox::make('create_report_cards')
                        ->label('Create/update report cards')
                        ->default(true),
                ])
                ->action(fn (array $data) => ListCompiledResults::compile($data)),
            CreateAction::make()
                ->visible(fn (): bool => ! TeacherWorkspace::isTeacher()),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::studentEnrollment(ReportCard::class, 'All report cards');
    }
}
