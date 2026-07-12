<?php

namespace App\Filament\Resources\CommunicationLogs;

use App\Filament\Resources\CommunicationLogs\Pages\ListCommunicationLogs;
use App\Filament\Resources\CommunicationLogs\Tables\CommunicationLogsTable;
use App\Models\CommunicationLog;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CommunicationLogResource extends Resource
{
    protected static ?string $model = CommunicationLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static ?string $navigationLabel = 'Message Delivery';

    protected static ?string $modelLabel = 'communication log';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 20;

    public static function canAccess(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return CommunicationLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommunicationLogs::route('/'),
        ];
    }
}
