<?php

namespace App\Filament\Resources\AccountTransfers\Schemas;

use App\Filament\Support\SchoolSelect;
use App\Models\LedgerAccount;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AccountTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transfer')
                    ->schema([
                        SchoolSelect::make(),
                        Select::make('from_account_id')
                            ->label('From account')
                            ->options(fn (): array => LedgerAccount::query()
                                ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                ->where('is_active', true)
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn (LedgerAccount $account): array => [$account->getKey() => "{$account->code} - {$account->name}"])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('to_account_id')
                            ->label('To account')
                            ->options(fn (): array => LedgerAccount::query()
                                ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                ->where('is_active', true)
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn (LedgerAccount $account): array => [$account->getKey() => "{$account->code} - {$account->name}"])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->different('from_account_id'),
                        TextInput::make('transfer_number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated'),
                        DatePicker::make('transfer_date')->required()->default(today()),
                        TextInput::make('amount')->numeric()->prefix('NGN')->required(),
                        TextInput::make('reference')->maxLength(255),
                        Select::make('status')->required()->default('posted')->options([
                            'draft' => 'Draft',
                            'posted' => 'Posted',
                            'cancelled' => 'Cancelled',
                        ]),
                        Textarea::make('notes')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
