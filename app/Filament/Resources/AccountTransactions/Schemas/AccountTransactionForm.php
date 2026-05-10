<?php

namespace App\Filament\Resources\AccountTransactions\Schemas;

use App\Filament\Support\SchoolSelect;
use App\Models\BankAccount;
use App\Models\LedgerAccount;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AccountTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction')
                    ->schema([
                        SchoolSelect::make(),
                        Select::make('ledger_account_id')
                            ->label('Ledger account')
                            ->options(fn (): array => LedgerAccount::query()
                                ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn (LedgerAccount $account): array => [$account->getKey() => "{$account->code} - {$account->name}"])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('bank_account_id')
                            ->label('Bank account')
                            ->options(fn (): array => BankAccount::query()
                                ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                ->orderBy('bank_name')
                                ->pluck('account_name', 'id')
                                ->all())
                            ->searchable()
                            ->preload(),
                        TextInput::make('transaction_number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated'),
                        DatePicker::make('transaction_date')->required()->default(today()),
                        Select::make('direction')
                            ->required()
                            ->options([
                                'debit' => 'Debit',
                                'credit' => 'Credit',
                            ]),
                        TextInput::make('amount')->numeric()->prefix('NGN')->required(),
                        TextInput::make('description')->required()->maxLength(255),
                        TextInput::make('reference')->maxLength(255),
                        Select::make('status')->required()->default('posted')->options([
                            'draft' => 'Draft',
                            'posted' => 'Posted',
                            'void' => 'Void',
                        ]),
                        Textarea::make('notes')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
