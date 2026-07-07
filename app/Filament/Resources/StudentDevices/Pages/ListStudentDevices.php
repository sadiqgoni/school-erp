<?php

namespace App\Filament\Resources\StudentDevices\Pages;

use App\Filament\Resources\StudentDevices\StudentDeviceResource;
use App\Models\School;
use App\Support\SafetyTransportSampleSetup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Str;

class ListStudentDevices extends ListRecords
{
    protected static string $resource = StudentDeviceResource::class;

    protected ?string $subheading = 'Scanners send scans to the school\'s device endpoint using the gateway token below.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('gatewayToken')
                ->label('Gateway token')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->modalHeading('Device gateway token')
                ->modalDescription(function (): string {
                    $school = Filament::getTenant();

                    if (blank($school?->device_api_token)) {
                        return 'No token yet. Click "Generate new token" to create one, then configure it on the NFC gateway.';
                    }

                    return 'Token: '.$school->device_api_token
                        ."\n\nScanners POST to ".route('devices.events')
                        .' with this token and the card identifier.';
                })
                ->modalSubmitActionLabel('Generate new token')
                ->action(function (): void {
                    $school = Filament::getTenant();

                    $school?->forceFill([
                        'device_api_token' => Str::random(48),
                    ])->save();

                    Notification::make()
                        ->title('New gateway token generated')
                        ->body('Update the NFC gateway with the new token — the old one no longer works.')
                        ->success()
                        ->send();
                }),
            Action::make('sampleDevices')
                ->label('Load sample data')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Create sample NFC and transport data?')
                ->modalDescription('This creates realistic sample devices, bus routes, route assignments, and scan movements for the current school section.')
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

                    if ($result['devices'] === 0) {
                        Notification::make()
                            ->title('No active students yet')
                            ->body('Create or load sample students first, then try again.')
                            ->warning()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Sample safety and transport data ready')
                        ->body("Saved {$result['devices']} devices, {$result['routes']} routes, {$result['assignments']} route assignments, and {$result['movements']} movements.")
                        ->success()
                        ->send();
                }),
            CreateAction::make()
                ->label('Register device')
                ->icon('heroicon-o-plus'),
        ];
    }
}
