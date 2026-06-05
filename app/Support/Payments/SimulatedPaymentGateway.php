<?php

namespace App\Support\Payments;

use App\Models\StudentInvoice;
use Illuminate\Support\Str;

class SimulatedPaymentGateway
{
    public function initialize(StudentInvoice $invoice): PaymentInitialization
    {
        $reference = $invoice->payment_reference ?: $this->makeReference($invoice);
        $url = route('payments.checkout', ['reference' => $reference]);

        return new PaymentInitialization(
            provider: 'simulated',
            reference: $reference,
            authorizationUrl: $url,
            payload: [
                'status' => true,
                'message' => 'Checkout URL created',
                'data' => [
                    'authorization_url' => $url,
                    'reference' => $reference,
                    'mode' => 'simulation',
                ],
            ],
        );
    }

    protected function makeReference(StudentInvoice $invoice): string
    {
        return 'SIM-'.$invoice->school_id.'-INV-'.$invoice->getKey().'-'.Str::upper(Str::random(8));
    }
}
