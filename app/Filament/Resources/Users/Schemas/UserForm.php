<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
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
                                User::SCHOOL_ROLE_ADMIN => 'Admin',
                                User::SCHOOL_ROLE_FINANCE => 'Finance',
                                User::SCHOOL_ROLE_TEACHER => 'Teacher',
                                User::SCHOOL_ROLE_STAFF => 'Staff',
                                User::SCHOOL_ROLE_PARENT => 'Parent',
                            ])
                            ->default(User::SCHOOL_ROLE_STAFF)
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
                            ->label('Superadmin')
                            ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
