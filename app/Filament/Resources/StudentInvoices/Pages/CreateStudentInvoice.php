<?php

namespace App\Filament\Resources\StudentInvoices\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\StudentInvoices\StudentInvoiceResource;
use App\Models\FeeType;
use App\Models\StudentInvoice;
use App\Models\StudentInvoiceItem;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateStudentInvoice extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = StudentInvoiceResource::class;

    public function getTitle(): string
    {
        return 'Create Student Invoice';
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $itemEntries = $data['item_entries'] ?? [];

            unset($data['item_entries']);

            $data['school_id'] ??= Filament::getTenant()?->getKey();
            $data['subtotal'] = 0;
            $data['total'] = 0;
            $data['amount_paid'] = 0;
            $data['balance'] = 0;

            /** @var StudentInvoice $invoice */
            $invoice = static::getModel()::query()->create($data);

            foreach ($itemEntries as $itemEntry) {
                if (blank($itemEntry['fee_type_id'] ?? null) || blank($itemEntry['amount'] ?? null)) {
                    continue;
                }

                StudentInvoiceItem::query()->create([
                    'school_id' => $invoice->school_id,
                    'student_invoice_id' => $invoice->getKey(),
                    'fee_type_id' => $itemEntry['fee_type_id'] ?: null,
                    'description' => FeeType::query()->find($itemEntry['fee_type_id'])?->name ?? 'Charge',
                    'amount' => $itemEntry['amount'],
                ]);
            }

            $invoice->refreshAmounts();

            return $invoice->fresh();
        });
    }
}
