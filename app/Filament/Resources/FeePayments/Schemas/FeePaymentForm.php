<?php

namespace App\Filament\Resources\FeePayments\Schemas;

use App\Filament\Support\SchoolSelect;
use App\Models\BankAccount;
use App\Models\LedgerAccount;
use App\Models\StudentInvoice;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class FeePaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment')
                    ->schema([
                        SchoolSelect::make(),
                        Select::make('student_invoice_id')
                            ->relationship('studentInvoice', 'invoice_number')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                $invoice = $state ? StudentInvoice::query()->find($state) : null;

                                $set('student_id', $invoice?->student_id);
                            }),
                        Select::make('student_id')
                            ->relationship('student', 'admission_number')
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder(fn (Get $get): ?string => StudentInvoice::query()->find($get('student_invoice_id'))?->student?->admission_number),
                        TextInput::make('receipt_number')
                            ->maxLength(60)
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated'),
                        TextInput::make('payer')->maxLength(255),
                        DatePicker::make('payment_date')->required()->default(today()),
                        TextInput::make('amount')->numeric()->prefix('NGN')->required(),
                        Select::make('payment_method')->required()->default('cash')->options([
                            'cash' => 'Cash',
                            'bank_transfer' => 'Bank transfer',
                            'pos' => 'POS',
                            'card' => 'Card',
                            'online' => 'Online',
                        ]),
                        Select::make('bank_account_id')
                            ->label('Bank account')
                            ->options(fn (): array => BankAccount::query()
                                ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                ->where('is_active', true)
                                ->orderByDesc('is_default')
                                ->orderBy('bank_name')
                                ->pluck('account_name', 'id')
                                ->all())
                            ->searchable()
                            ->preload(),
                        Select::make('asset_account_id')
                            ->label('Asset account')
                            ->options(fn (): array => LedgerAccount::query()
                                ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                ->where('type', 'asset')
                                ->where('is_active', true)
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn (LedgerAccount $account): array => [$account->getKey() => "{$account->code} - {$account->name}"])
                                ->all())
                            ->searchable()
                            ->preload(),
                        Select::make('income_account_id')
                            ->label('Income account')
                            ->options(fn (): array => LedgerAccount::query()
                                ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                ->where('type', 'income')
                                ->where('is_active', true)
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn (LedgerAccount $account): array => [$account->getKey() => "{$account->code} - {$account->name}"])
                                ->all())
                            ->searchable()
                            ->preload(),
                        TextInput::make('reference')->maxLength(255),
                        Select::make('received_by_id')->relationship('receivedBy', 'name')->searchable()->preload(),
                        Select::make('status')->required()->default('confirmed')->options([
                            'pending' => 'Pending',
                            'confirmed' => 'Confirmed',
                            'failed' => 'Failed',
                            'reversed' => 'Reversed',
                        ]),
                        Textarea::make('notes')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
