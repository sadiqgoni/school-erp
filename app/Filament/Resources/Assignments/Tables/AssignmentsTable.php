<?php

namespace App\Filament\Resources\Assignments\Tables;

use App\Models\Assignment;
use App\Models\Enrollment;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class AssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['schoolClass', 'classSection', 'subject', 'staff'])
                ->withCount('confirmations'))
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->weight('semibold')
                    ->wrap()
                    ->description(fn (Assignment $record): ?string => $record->subject?->name),
                TextColumn::make('schoolClass.name')
                    ->label('Class')
                    ->state(fn (Assignment $record): string => $record->classLabel() ?: 'Not set')
                    ->badge()
                    ->color('info'),
                TextColumn::make('staff.full_name')
                    ->label('Teacher')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('assigned_on')
                    ->label('Given')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('due_on')
                    ->label('Due')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('No due date')
                    ->color(fn (Assignment $record): string => $record->due_on?->isPast() ? 'danger' : 'success'),
                TextColumn::make('confirmations_count')
                    ->label('Parents confirmed')
                    ->state(fn (Assignment $record): string => $record->confirmations_count.' of '.self::classSize($record))
                    ->badge()
                    ->color(fn (Assignment $record): string => $record->confirmations_count >= self::classSize($record) && self::classSize($record) > 0
                        ? 'success'
                        : 'warning'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => $state === 'published' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('school_class_id')
                    ->label('Class')
                    ->relationship('schoolClass', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        'published' => 'Published',
                        'draft' => 'Draft',
                    ]),
            ])
            ->recordActions([
                Action::make('attachment')
                    ->label('Attachment')
                    ->icon('heroicon-o-paper-clip')
                    ->visible(fn (Assignment $record): bool => filled($record->attachment_path))
                    ->url(fn (Assignment $record): string => Storage::disk('public')->url($record->attachment_path))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('assigned_on', 'desc')
            ->emptyStateHeading('No assignments yet')
            ->emptyStateDescription('Create an assignment and the parents of that class will see it instantly.')
            ->striped();
    }

    protected static function classSize(Assignment $record): int
    {
        static $sizes = [];

        $key = $record->school_class_id.':'.($record->class_section_id ?? 'all');

        if (! array_key_exists($key, $sizes)) {
            $sizes[$key] = Enrollment::query()
                ->where('school_id', Filament::getTenant()?->getKey() ?? $record->school_id)
                ->where('school_class_id', $record->school_class_id)
                ->when($record->class_section_id, fn (Builder $query, $sectionId) => $query->where('class_section_id', $sectionId))
                ->where('status', 'active')
                ->count();
        }

        return $sizes[$key];
    }
}
