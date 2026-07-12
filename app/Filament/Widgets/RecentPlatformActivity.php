<?php

namespace App\Filament\Widgets;

use App\Models\UserActivity;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentPlatformActivity extends TableWidget
{
    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Platform Activity')
            ->query(fn (): Builder => UserActivity::query()->with(['user', 'school'])->latest())
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->since()
                    ->dateTimeTooltip(),
                TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'login' => 'success',
                        'logout' => 'gray',
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'info',
                    }),
                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('System'),
                TextColumn::make('school.name')
                    ->label('School')
                    ->placeholder('Platform')
                    ->badge()
                    ->color('info'),
                TextColumn::make('description')
                    ->wrap(),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10);
    }
}
