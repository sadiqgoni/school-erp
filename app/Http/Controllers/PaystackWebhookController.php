<?php

namespace App\Http\Controllers;

use App\Support\Payments\PaymentSettlementService;
use App\Support\Payments\PaystackGateway;
use Illuminate\Http\Request;
use Throwable;

class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackGateway $gateway, PaymentSettlementService $settlement)
    {
        $payload = $request->getContent();

        if (! $gateway->isValidSignature($payload, $request->header('x-paystack-signature'))) {
            abort(401);
        }

        $event = $request->json()->all();

        if (($event['event'] ?? null) !== 'charge.success') {
            return response()->noContent();
        }

        try {
            $reference = (string) data_get($event, 'data.reference');
            $transaction = $gateway->verify($reference);
            $settlement->settlePaystackTransaction($transaction);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Webhook received but settlement failed.'], 202);
        }

        return response()->noContent();
    }
}
