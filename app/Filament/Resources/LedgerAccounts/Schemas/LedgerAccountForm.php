<?php

namespace App\Filament\Resources\LedgerAccounts\Schemas;

use App\Filament\Support\SchoolSelect;
use App\Models\LedgerAccount;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LedgerAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ledger account')
                    ->schema([
                        SchoolSelect::make(),
                        TextInput::make('code')->required()->maxLength(40),
                        TextInput::make('name')->required()->maxLength(255),
                        Select::make('type')
                            ->required()
                            ->options([
                                'asset' => 'Asset',
                                'income' => 'Income',
                                'expense' => 'Expense',
                                'liability' => 'Liability',
                                'equity' => 'Equity',
                            ]),
                        Select::make('parent_id')
                            ->label('Parent account')
                            ->options(fn (): array => LedgerAccount::query()
                                ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn (LedgerAccount $account): array => [$account->getKey() => "{$account->code} - {$account->name}"])
                                ->all())
                            ->searchable()
                            ->preload(),
                        TextInput::make('opening_balance')->numeric()->prefix('NGN')->default(0),
                        Toggle::make('is_system')->default(false),
                        Toggle::make('is_active')->default(true),
                        Textarea::make('description')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
