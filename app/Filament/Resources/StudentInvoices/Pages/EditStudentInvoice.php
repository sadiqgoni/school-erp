<?php

namespace App\Filament\Resources\StudentInvoices\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\StudentInvoices\StudentInvoiceResource;
use App\Models\FeeType;
use App\Models\StudentInvoiceItem;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditStudentInvoice extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = StudentInvoiceResource::class;

    public function getTitle(): string
    {
        return 'Edit Student Invoice';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['item_entries'] = $this->getRecord()->items()
            ->get()
            ->map(fn (StudentInvoiceItem $item): array => [
                'fee_type_id' => $item->fee_type_id,
                'amount' => $item->amount,
            ])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $itemEntries = $data['item_entries'] ?? [];

            unset($data['item_entries'], $data['subtotal'], $data['total'], $data['amount_paid'], $data['balance']);

            $record->update($data);

            $record->items()->delete();

            foreach ($itemEntries as $itemEntry) {
                if (blank($itemEntry['fee_type_id'] ?? null) || blank($itemEntry['amount'] ?? null)) {
                    continue;
                }

                StudentInvoiceItem::query()->create([
                    'school_id' => $record->school_id,
                    'student_invoice_id' => $record->getKey(),
                    'fee_type_id' => $itemEntry['fee_type_id'] ?: null,
                    'description' => FeeType::query()->find($itemEntry['fee_type_id'])?->name ?? 'Charge',
                    'amount' => $itemEntry['amount'],
                ]);
            }

            $record->refreshAmounts();

            return $record->fresh();
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
