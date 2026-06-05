<?php

namespace App\Filament\Resources\ParentInvoices\Tables;

use App\Models\StudentInvoice;
use App\Support\Payments\SimulatedPaymentGateway;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ParentInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => self::scopeToParent($query))
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn (StudentInvoice $record): ?string => $record->student?->admission_number),
                TextColumn::make('student.enrollments.schoolClass.name')
                    ->label('Class')
                    ->state(fn (StudentInvoice $record): string => self::placementLabel($record))
                    ->badge(),
                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->description(fn (StudentInvoice $record): ?string => $record->invoice_date?->format('d M Y')),
                TextColumn::make('invoice_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('total')
                    ->money('NGN')
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('NGN')
                    ->sortable(),
                TextColumn::make('balance')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold'),
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
                SelectFilter::make('status')->options([
                    'unpaid' => 'Unpaid',
                    'partial' => 'Partial',
                    'paid' => 'Paid',
                    'overdue' => 'Overdue',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('student_id')
                    ->label('Child')
                    ->options(fn (): array => self::childOptions())
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('downloadPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (StudentInvoice $record): string => route('student-invoices.pdf', $record))
                    ->openUrlInNewTab(),
                Action::make('pay')
                    ->label('Pay')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->visible(fn (StudentInvoice $record): bool => (float) $record->balance > 0 && $record->status !== 'cancelled')
                    ->url(fn (StudentInvoice $record): string => self::paymentUrl($record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('invoice_date', 'desc')
            ->emptyStateHeading('No invoices yet')
            ->emptyStateDescription('Invoices for your linked children will appear here when the school creates them.')
            ->striped();
    }

    protected static function scopeToParent(Builder $query): Builder
    {
        $userId = Filament::auth()->id();
        $tenant = Filament::getTenant();

        return $query
            ->with(['student.enrollments.schoolClass', 'student.enrollments.classSection'])
            ->where('school_id', $tenant?->getKey())
            ->whereHas('student.guardianLinks.guardian', fn (Builder $query) => $query->where('user_id', $userId));
    }

    protected static function paymentUrl(StudentInvoice $invoice): string
    {
        if (filled($invoice->payment_url)) {
            return $invoice->payment_url;
        }

        $initialization = app(SimulatedPaymentGateway::class)->initialize($invoice);

        $invoice->forceFill([
            'payment_provider' => $initialization->provider,
            'payment_reference' => $initialization->reference,
            'payment_url' => $initialization->authorizationUrl,
            'payment_status' => 'initialized',
            'payment_metadata' => $initialization->payload,
        ])->save();

        return $initialization->authorizationUrl;
    }

    protected static function childOptions(): array
    {
        $userId = Filament::auth()->id();
        $tenant = Filament::getTenant();

        return \App\Models\Student::query()
            ->where('school_id', $tenant?->getKey())
            ->whereHas('guardianLinks.guardian', fn (Builder $query) => $query->where('user_id', $userId))
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(fn ($student): array => [$student->getKey() => $student->full_name])
            ->all();
    }

    protected static function placementLabel(StudentInvoice $record): string
    {
        $placement = $record->student?->enrollments->sortByDesc('enrolled_on')->first();

        return collect([
            $placement?->schoolClass?->name,
            $placement?->classSection?->name,
        ])->filter()->implode(' ') ?: 'Not set';
    }
}
