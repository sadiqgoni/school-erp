<?php

namespace App\Http\Controllers;

use App\Support\Payments\MonnifyGateway;
use App\Support\Payments\PaymentSettlementService;
use Illuminate\Http\Request;
use Throwable;

class MonnifyWebhookController extends Controller
{
    public function __invoke(Request $request, MonnifyGateway $gateway, PaymentSettlementService $settlement)
    {
        $payload = $request->getContent();

        if (! $gateway->isValidSignature($payload, $request->header('monnify-signature'))) {
            abort(401);
        }

        $event = $request->json()->all();

        if (($event['eventType'] ?? null) !== 'SUCCESSFUL_TRANSACTION') {
            return response()->noContent();
        }

        try {
            $reference = (string) data_get($event, 'eventData.paymentReference');
            $transaction = $gateway->verify($reference);
            $settlement->settleMonnifyTransaction($transaction);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Webhook received but settlement failed.'], 202);
        }

        return response()->noContent();
    }
}
