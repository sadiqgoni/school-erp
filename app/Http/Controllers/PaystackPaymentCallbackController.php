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

            return redirect()
                ->route('payments.receipt', [
                    'reference' => $reference,
                    'status' => 'failed',
                ]);
        }

        if (! $payment) {
            return redirect()
                ->route('payments.receipt', [
                    'reference' => $reference,
                    'status' => 'unmatched',
                ]);
        }

        return redirect()
            ->route('payments.receipt', [
                'reference' => $reference,
                'status' => 'success',
            ]);
    }
}
