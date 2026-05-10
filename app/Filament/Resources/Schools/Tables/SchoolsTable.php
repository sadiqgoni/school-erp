<?php

namespace App\Filament\Resources\Schools\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SchoolsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->visibility('public')
                    ->defaultImageUrl(asset('images/branding/school-dice-logo-icon.png'))
                    ->circular(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): string => collect([$record->code, $record->email, $record->phone])->filter()->join(' | ')),
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Portal URL')
                    ->state(fn ($record): string => "/portal/{$record->slug}")
                    ->copyable()
                    ->copyMessage('Portal URL copied')
                    ->toggleable(),
                TextColumn::make('city')
                    ->state(fn ($record): string => collect([$record->city, $record->state])->filter()->join(', '))
                    ->label('Location')
                    ->toggleable(),
                TextColumn::make('subscription_plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'trial' => 'gray',
                        'basic_ngn' => 'info',
                        'standard_ngn' => 'success',
                        'premium_ngn' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('student_limit')
                    ->numeric()
                    ->sortable()
                    ->label('Capacity'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('subscription_plan')
                    ->options([
                        'trial' => 'Free Trial',
                        'basic_ngn' => 'Basic',
                        'standard_ngn' => 'Standard',
                        'premium_ngn' => 'Premium',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                Action::make('openPortal')
                    ->label('Open portal')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record): string => url("/portal/{$record->slug}"))
                    ->openUrlInNewTab(),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No schools yet')
            ->emptyStateDescription('Create the first school and its login to open the school portal.')
            ->striped();
    }
}
