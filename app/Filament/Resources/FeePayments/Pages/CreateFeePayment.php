<?php

namespace App\Filament\Resources\FeePayments\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\FeePayments\FeePaymentResource;
use App\Models\StudentInvoice;
use Filament\Resources\Pages\CreateRecord;

class CreateFeePayment extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = FeePaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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
}
