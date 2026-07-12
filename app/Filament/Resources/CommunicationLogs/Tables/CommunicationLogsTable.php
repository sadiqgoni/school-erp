<?php

namespace App\Filament\Resources\CommunicationLogs\Tables;

use App\Models\CommunicationLog;
use App\Support\WhatsApp;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommunicationLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('school'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Queued')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('school.name')
                    ->label('School')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('event_type')
                    ->label('Event')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? str($state)->replace('_', ' ')->title()->toString()
                        : '—')
                    ->color('gray'),
                TextColumn::make('channel')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                TextColumn::make('recipient_name')
                    ->label('Recipient')
                    ->searchable()
                    ->description(fn (CommunicationLog $record): ?string => $record->recipient_contact),
                TextColumn::make('subject')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent', 'delivered' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('failure_reason')
                    ->label('Failure reason')
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('school_id')
                    ->label('School')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('event_type')
                    ->options([
                        'fee_invoice_created' => 'Invoice created',
                        'fee_due_reminder' => 'Fee due reminder',
                        'fee_payment_received' => 'Payment received',
                    ]),
                SelectFilter::make('channel')
                    ->options([
                        'sms' => 'SMS',
                        'email' => 'Email',
                        'whatsapp' => 'WhatsApp',
                        'in_app' => 'In app',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'queued' => 'Queued',
                        'sent' => 'Sent',
                        'delivered' => 'Delivered',
                        'failed' => 'Failed',
                    ]),
            ])
            ->recordActions([
                Action::make('openWhatsApp')
                    ->label('Send via WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn (CommunicationLog $record): bool => $record->status === 'queued'
                        && filled(WhatsApp::normalizePhone($record->recipient_contact)))
                    ->url(fn (CommunicationLog $record): ?string => WhatsApp::link(
                        $record->recipient_contact,
                        $record->body ?: ($record->subject ?: 'Reminder from '.($record->school?->name ?? 'school')),
                    ))
                    ->openUrlInNewTab(),
                Action::make('markSent')
                    ->label('Mark as sent')
                    ->icon('heroicon-o-check-circle')
                    ->color('gray')
                    ->visible(fn (CommunicationLog $record): bool => $record->status === 'queued')
                    ->requiresConfirmation()
                    ->modalDescription('Use this once you have manually delivered the message (e.g. via WhatsApp) so it stops showing as pending.')
                    ->action(function (CommunicationLog $record): void {
                        $record->forceFill([
                            'status' => 'sent',
                            'sent_at' => now(),
                        ])->save();

                        Notification::make()
                            ->title('Marked as sent')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No communications logged yet')
            ->emptyStateDescription('Invoice reminders and payment confirmations queued from any school will appear here.')
            ->striped();
    }
}
