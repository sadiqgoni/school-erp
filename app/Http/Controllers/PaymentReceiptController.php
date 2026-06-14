<?php

namespace App\Http\Controllers;

use App\Models\StudentInvoice;
use Illuminate\Http\Request;

class PaymentReceiptController extends Controller
{
    public function __invoke(Request $request)
    {
        $reference = (string) $request->query('reference');

        abort_if(blank($reference), 404);

        $invoice = StudentInvoice::query()
            ->with(['school', 'student', 'payments' => fn ($query) => $query->latest('payment_date')->latest('id')])
            ->where('payment_reference', $reference)
            ->firstOrFail();

        return view('payments.receipt', [
            'invoice' => $invoice,
            'payment' => $invoice->payments->firstWhere('reference', $reference) ?? $invoice->payments->first(),
            'status' => (string) $request->query('status', $invoice->payment_status),
            'portalUrl' => $invoice->school?->slug
                ? url("/portal/{$invoice->school->slug}/parent-invoices")
                : null,
        ]);
    }
}
