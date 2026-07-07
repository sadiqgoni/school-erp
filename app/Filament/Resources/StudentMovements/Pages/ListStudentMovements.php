<?php

namespace App\Filament\Resources\StudentMovements\Pages;

use App\Filament\Resources\StudentMovements\StudentMovementResource;
use App\Models\BusRoute;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMovement;
use App\Support\SafetyTransportSampleSetup;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;

class ListStudentMovements extends ListRecords
{
    protected static string $resource = StudentMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleMovements')
                ->label('Load sample data')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Create sample arrivals and exits?')
                ->modalDescription('This generates realistic NFC devices, bus routes, and recent movement history for active students.')
                ->action(function (): void {
                    $school = Filament::getTenant();

                    if (! $school instanceof School) {
                        Notification::make()
                            ->title('Open a school section first')
                            ->warning()
                            ->send();

                        return;
                    }

                    $result = SafetyTransportSampleSetup::createForSchool($school);

                    if ($result['movements'] === 0) {
                        Notification::make()
                            ->title('No active students yet')
                            ->body('Create or load sample students first, then try again.')
                            ->warning()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Sample movement history ready')
                        ->body("Saved {$result['movements']} movements for {$result['devices']} student devices.")
                        ->success()
                        ->send();
                }),
            Action::make('recordMovement')
                ->label('Record movement')
                ->icon('heroicon-o-plus')
                ->modalHeading('Record a student movement')
                ->modalDescription('Use this when there is no scanner — e.g. gate staff marking arrival, exit, or bus boarding by hand.')
                ->schema([
                    Select::make('student_id')
                        ->label('Student')
                        ->options(fn (): array => Student::query()
                            ->where('school_id', Filament::getTenant()?->getKey())
                            ->where('status', 'active')
                            ->orderBy('first_name')
                            ->get()
                            ->mapWithKeys(fn (Student $student): array => [
                                $student->getKey() => $student->full_name.' ('.$student->admission_number.')',
                            ])
                            ->all())
                        ->searchable()
                        ->required(),
                    Select::make('event_type')
                        ->label('Event')
                        ->options(StudentMovement::EVENTS)
                        ->default(StudentMovement::EVENT_CHECK_IN)
                        ->live()
                        ->required(),
                    Select::make('bus_route_id')
                        ->label('Bus route')
                        ->options(fn (): array => BusRoute::query()
                            ->where('school_id', Filament::getTenant()?->getKey())
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->visible(fn (Get $get): bool => in_array($get('event_type'), [
                            StudentMovement::EVENT_BUS_BOARDED,
                            StudentMovement::EVENT_BUS_DROPPED,
                        ], true)),
                    DateTimePicker::make('happened_at')
                        ->label('When')
                        ->default(now())
                        ->seconds(false)
                        ->required(),
                    TextInput::make('notes')
                        ->label('Note (optional)')
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    StudentMovement::query()->create([
                        'school_id' => Filament::getTenant()?->getKey(),
                        'student_id' => $data['student_id'],
                        'bus_route_id' => $data['bus_route_id'] ?? null,
                        'event_type' => $data['event_type'],
                        'happened_at' => $data['happened_at'],
                        'source' => 'manual',
                        'recorded_by' => Filament::auth()->id(),
                        'notes' => $data['notes'] ?? null,
                    ]);

                    Notification::make()
                        ->title('Movement recorded — parents can see it now.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
