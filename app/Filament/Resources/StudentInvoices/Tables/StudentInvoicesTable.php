<?php

namespace App\Filament\Resources\StudentInvoices\Tables;

use App\Support\PaymentCommunicationCoordinator;
use App\Support\Payments\PaystackGateway;
use App\Support\Payments\SimulatedPaymentGateway;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;

class StudentInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('invoice_number')->searchable()->sortable(),
                TextColumn::make('invoice_type')->label('Type')->badge()->sortable(),
                TextColumn::make('student.admission_number')->label('Admission no.')->searchable()->sortable(),
                TextColumn::make('student.full_name')->label('Student')->searchable(),
                TextColumn::make('student.enrollments.schoolClass.name')
                    ->label('Class')
                    ->badge()
                    ->separator(',')
                    ->placeholder('No class')
                    ->toggleable(),
                TextColumn::make('discount')->money('NGN')->sortable()->toggleable(),
                TextColumn::make('total')->money('NGN')->sortable(),
                TextColumn::make('amount_paid')->money('NGN')->sortable(),
                TextColumn::make('balance')->money('NGN')->sortable(),
                TextColumn::make('payment_provider')
                    ->label('Gateway')
                    ->badge()
                    ->placeholder('Manual')
                    ->toggleable(),
                TextColumn::make('payment_status')
                    ->label('Gateway status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'initialized' => 'info',
                        'paid' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        default => 'info',
                    }),
            ])
            ->filters([
                SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                SelectFilter::make('status')->options([
                    'unpaid' => 'Unpaid',
                    'partial' => 'Partial',
                    'paid' => 'Paid',
                    'overdue' => 'Overdue',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('invoice_type')
                    ->label('Type')
                    ->options([
                        'standard' => 'Standard invoice',
                        'emergency' => 'Emergency / one-off invoice',
                    ]),
            ])
            ->recordActions([
                Action::make('downloadPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record): string => route('student-invoices.pdf', $record))
                    ->openUrlInNewTab(),
                Action::make('initializePaystack')
                    ->label('Paystack link')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->visible(fn ($record): bool => (float) $record->balance > 0)
                    ->action(function ($record): void {
                        try {
                            $initialization = app(PaystackGateway::class)->initialize($record);
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('Payment link failed')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->forceFill([
                            'payment_provider' => $initialization->provider,
                            'payment_reference' => $initialization->reference,
                            'payment_url' => $initialization->authorizationUrl,
                            'payment_status' => 'initialized',
                            'payment_metadata' => $initialization->payload,
                        ])->save();

                        Notification::make()
                            ->title('Payment link ready')
                            ->body('Paystack checkout link has been saved on the invoice.')
                            ->success()
                            ->send();
                    }),
                Action::make('initializeSimulation')
                    ->label('Checkout link')
                    ->icon('heroicon-o-beaker')
                    ->color('warning')
                    ->visible(fn ($record): bool => (float) $record->balance > 0)
                    ->action(function ($record): void {
                        $initialization = app(SimulatedPaymentGateway::class)->initialize($record);

                        $record->forceFill([
                            'payment_provider' => $initialization->provider,
                            'payment_reference' => $initialization->reference,
                            'payment_url' => $initialization->authorizationUrl,
                            'payment_status' => 'initialized',
                            'payment_metadata' => $initialization->payload,
                        ])->save();

                        Notification::make()
                            ->title('Checkout link ready')
                            ->body('A test checkout link has been saved on the invoice.')
                            ->success()
                            ->send();
                    }),
                Action::make('openPaymentLink')
                    ->label('Open pay link')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->visible(fn ($record): bool => filled($record->payment_url))
                    ->url(fn ($record): string => $record->payment_url)
                    ->openUrlInNewTab(),
                Action::make('queueReminder')
                    ->label('Queue reminder')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('info')
                    ->action(function ($record): void {
                        $coordinator = app(PaymentCommunicationCoordinator::class);
                        $logs = $coordinator->queueInvoiceReminder($record, 'fee_reminder_manual');
                        $reminders = $coordinator->scheduleInvoiceDueReminders($record);

                        Notification::make()
                            ->title('Fee reminder queued')
                            ->body("Created {$logs->count()} communication log(s) and {$reminders->count()} reminder(s).")
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
