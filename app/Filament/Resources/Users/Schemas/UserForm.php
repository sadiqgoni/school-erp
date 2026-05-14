<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        DateTimePicker::make('email_verified_at'),
                        Select::make('school_role')
                            ->label('School role')
                            ->options([
                                'school_admin' => 'School admin',
                                'teacher' => 'Teacher',
                                'staff' => 'Staff',
                            ])
                            ->default('staff')
                            ->required()
                            ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                            ->dehydrated(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school'),
                        Select::make('schools')
                            ->relationship('schools', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                        Toggle::make('is_platform_admin')
                            ->label('Platform admin')
                            ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
