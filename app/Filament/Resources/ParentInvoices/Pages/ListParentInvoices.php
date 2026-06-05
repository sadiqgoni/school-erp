<?php

namespace App\Filament\Resources\ParentInvoices\Pages;

use App\Filament\Resources\ParentInvoices\ParentInvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ListParentInvoices extends ListRecords
{
    protected static string $resource = ParentInvoiceResource::class;
}
