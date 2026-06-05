<?php

namespace App\Http\Controllers;

use App\Support\Payments\PaymentSettlementService;
use App\Support\Payments\PaystackGateway;
use Illuminate\Http\Request;
use Throwable;

class PaystackPaymentCallbackController extends Controller
{
    public function __invoke(Request $request, PaystackGateway $gateway, PaymentSettlementService $settlement)
    {
        $reference = (string) $request->query('reference');

        abort_if(blank($reference), 404);

        try {
            $transaction = $gateway->verify($reference);
            $payment = $settlement->settlePaystackTransaction($transaction);
        } catch (Throwable $exception) {
            report($exception);

            return response('Payment verification failed. Please contact the school with reference: '.$reference, 502);
        }

        if (! $payment) {
            return response('Payment was not successful or could not be matched. Reference: '.$reference, 202);
        }

        return response('Payment received successfully. Receipt: '.$payment->receipt_number);
    }
}
