<?php

namespace App\Http\Controllers;

use App\Models\StudentInvoice;
use Illuminate\Http\Request;

class SimulatedPaymentCheckoutController extends Controller
{
    public function show(Request $request)
    {
        $reference = (string) $request->query('reference');

        abort_if(blank($reference), 404);

        $invoice = StudentInvoice::query()
            ->with(['school', 'student'])
            ->where('payment_provider', 'simulated')
            ->where('payment_reference', $reference)
            ->firstOrFail();

        return view('payments.simulated-checkout', [
            'invoice' => $invoice,
        ]);
    }
}
