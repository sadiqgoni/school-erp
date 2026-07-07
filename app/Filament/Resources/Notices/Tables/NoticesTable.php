<?php

namespace App\Filament\Resources\Notices\Tables;

use App\Models\Notice;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class NoticesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['schoolClass', 'classSection', 'author']))
            ->columns([
                IconColumn::make('is_pinned')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                TextColumn::make('title')
                    ->searchable()
                    ->weight('semibold')
                    ->wrap()
                    ->description(fn (Notice $record): ?string => $record->author?->name
                        ? 'By '.$record->author->name
                        : null),
                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'fees' => 'warning',
                        'event' => 'info',
                        'newsletter' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('audience_type')
                    ->label('Audience')
                    ->state(fn (Notice $record): string => $record->audienceLabel())
                    ->badge()
                    ->color('info'),
                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('expires_on')
                    ->label('Shows until')
                    ->date('d M Y')
                    ->placeholder('No expiry')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => $state === Notice::STATUS_PUBLISHED ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'general' => 'General',
                        'event' => 'Event',
                        'fees' => 'Fees',
                        'exam' => 'Exams',
                        'newsletter' => 'Newsletter',
                        'urgent' => 'Urgent',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        Notice::STATUS_PUBLISHED => 'Published',
                        Notice::STATUS_DRAFT => 'Draft',
                    ]),
            ])
            ->recordActions([
                Action::make('attachment')
                    ->label('File')
                    ->icon('heroicon-o-paper-clip')
                    ->visible(fn (Notice $record): bool => filled($record->attachment_path))
                    ->url(fn (Notice $record): string => Storage::disk('public')->url($record->attachment_path))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('published_at', 'desc')
            ->emptyStateHeading('No notices yet')
            ->emptyStateDescription('Type a notice or upload the printed newsletter — parents see it instantly.')
            ->striped();
    }
}
