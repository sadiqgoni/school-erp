<?php

namespace App\Filament\Resources\StudentDiscounts;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\StudentDiscounts\Pages\CreateStudentDiscount;
use App\Filament\Resources\StudentDiscounts\Pages\EditStudentDiscount;
use App\Filament\Resources\StudentDiscounts\Pages\ListStudentDiscounts;
use App\Filament\Resources\StudentDiscounts\Schemas\StudentDiscountForm;
use App\Filament\Resources\StudentDiscounts\Tables\StudentDiscountsTable;
use App\Models\StudentDiscount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StudentDiscountResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = StudentDiscount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $navigationLabel = 'Student Discounts';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance Setup';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return StudentDiscountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentDiscountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudentDiscounts::route('/'),
            'create' => CreateStudentDiscount::route('/create'),
            'edit' => EditStudentDiscount::route('/{record}/edit'),
        ];
    }
}
