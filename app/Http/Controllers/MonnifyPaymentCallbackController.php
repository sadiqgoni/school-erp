<?php

namespace App\Http\Controllers;

use App\Support\Payments\MonnifyGateway;
use App\Support\Payments\PaymentSettlementService;
use Illuminate\Http\Request;
use Throwable;

class MonnifyPaymentCallbackController extends Controller
{
    public function __invoke(Request $request, MonnifyGateway $gateway, PaymentSettlementService $settlement)
    {
        $reference = (string) $request->query('reference');

        abort_if(blank($reference), 404);

        try {
            $transaction = $gateway->verify($reference);
            $payment = $settlement->settleMonnifyTransaction($transaction);
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
