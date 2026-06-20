<?php

namespace App\Filament\Resources\SalaryTemplates;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\SalaryTemplates\Pages\CreateSalaryTemplate;
use App\Filament\Resources\SalaryTemplates\Pages\EditSalaryTemplate;
use App\Filament\Resources\SalaryTemplates\Pages\ListSalaryTemplates;
use App\Filament\Resources\SalaryTemplates\Schemas\SalaryTemplateForm;
use App\Filament\Resources\SalaryTemplates\Tables\SalaryTemplatesTable;
use App\Models\SalaryTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SalaryTemplateResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = SalaryTemplate::class;

    protected static ?string $navigationLabel = 'Salary Scale';

    protected static ?string $modelLabel = 'salary scale';

    protected static ?string $pluralModelLabel = 'salary scale';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Payroll';

    protected static ?int $navigationSort = 45;

    public static function form(Schema $schema): Schema
    {
        return SalaryTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalaryTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalaryTemplates::route('/'),
            'create' => CreateSalaryTemplate::route('/create'),
            'edit' => EditSalaryTemplate::route('/{record}/edit'),
        ];
    }
}
