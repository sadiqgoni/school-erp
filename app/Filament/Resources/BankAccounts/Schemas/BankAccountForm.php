<?php

namespace App\Filament\Resources\BankAccounts\Schemas;

use App\Filament\Support\SchoolSelect;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Bank account')
                    ->schema([
                        SchoolSelect::make(),
                        TextInput::make('bank_name')->required()->maxLength(255),
                        TextInput::make('account_name')->required()->maxLength(255),
                        TextInput::make('account_number')->required()->maxLength(80),
                        TextInput::make('branch')->maxLength(255),
                        TextInput::make('opening_balance')->numeric()->prefix('NGN')->default(0),
                        Toggle::make('is_default')->default(false),
                        Toggle::make('is_active')->default(true),
                        Textarea::make('notes')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
