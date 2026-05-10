<?php

namespace App\Filament\Resources\Expenses\Schemas;

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

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Expense')
                    ->schema([
                        SchoolSelect::make(),
                        Select::make('expense_category_id')->relationship('expenseCategory', 'name')->searchable()->preload()->required(),
                        TextInput::make('expense_number')
                            ->maxLength(60)
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated'),
                        DatePicker::make('expense_date')->required()->default(today()),
                        TextInput::make('payee')->maxLength(255),
                        TextInput::make('description')->required()->maxLength(255),
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
                            ->label('Paid from account')
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
                        Select::make('expense_account_id')
                            ->label('Expense account')
                            ->options(fn (): array => LedgerAccount::query()
                                ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                ->where('type', 'expense')
                                ->where('is_active', true)
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn (LedgerAccount $account): array => [$account->getKey() => "{$account->code} - {$account->name}"])
                                ->all())
                            ->searchable()
                            ->preload(),
                        TextInput::make('reference')->maxLength(255),
                        Select::make('recorded_by_id')->relationship('recordedBy', 'name')->searchable()->preload(),
                        Select::make('status')->required()->default('approved')->options([
                            'draft' => 'Draft',
                            'approved' => 'Approved',
                            'paid' => 'Paid',
                            'cancelled' => 'Cancelled',
                        ]),
                        Textarea::make('notes')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
