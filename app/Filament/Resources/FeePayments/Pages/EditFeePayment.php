<?php

namespace App\Filament\Resources\FeePayments\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\FeePayments\FeePaymentResource;
use App\Models\StudentInvoice;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeePayment extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = FeePaymentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['student_invoice_id'] ?? null)) {
            $invoice = StudentInvoice::query()->find($data['student_invoice_id']);

            if ($invoice) {
                $data['school_id'] = $invoice->school_id;
                $data['student_id'] = $invoice->student_id;
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
