<?php

namespace App\Filament\Resources\StudentInvoices;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\StudentInvoices\Pages\CreateStudentInvoice;
use App\Filament\Resources\StudentInvoices\Pages\EditStudentInvoice;
use App\Filament\Resources\StudentInvoices\Pages\ListStudentInvoices;
use App\Filament\Resources\StudentInvoices\Schemas\StudentInvoiceForm;
use App\Filament\Resources\StudentInvoices\Tables\StudentInvoicesTable;
use App\Models\StudentInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StudentInvoiceResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = StudentInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Invoices';

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & Payments';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return StudentInvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentInvoicesTable::configure($table);
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
            'index' => ListStudentInvoices::route('/'),
            'create' => CreateStudentInvoice::route('/create'),
            'edit' => EditStudentInvoice::route('/{record}/edit'),
        ];
    }
}
