<?php

namespace App\Http\Controllers;

use App\Models\StudentInvoice;
use App\Support\Payments\PaymentSettlementService;
use Illuminate\Http\Request;

class SimulatedPaymentCompleteController extends Controller
{
    public function __invoke(Request $request, PaymentSettlementService $settlement)
    {
        $validated = $request->validate([
            'reference' => ['required', 'string'],
            'outcome' => ['required', 'in:success,failed'],
            'payment_method' => ['required', 'in:card,bank_transfer,online_banking'],
            'bank' => ['nullable', 'string', 'max:80'],
            'teller_number' => ['nullable', 'string', 'max:80'],
            'card_last4' => ['nullable', 'string', 'max:4'],
        ]);

        $invoice = StudentInvoice::query()
            ->with(['student.guardianLinks.guardian'])
            ->where('payment_provider', 'simulated')
            ->where('payment_reference', $validated['reference'])
            ->firstOrFail();

        if ($validated['outcome'] === 'failed') {
            $invoice->forceFill([
                'payment_status' => 'failed',
                'payment_metadata' => array_filter(($invoice->payment_metadata ?? []) + [
                    'failed_at' => now()->toIso8601String(),
                    'simulation_outcome' => 'failed',
                    'channel' => $validated['payment_method'],
                ]),
            ])->save();

            return redirect()
                ->to($invoice->payment_url ?: route('payments.checkout', ['reference' => $invoice->payment_reference]))
                ->with('status', 'failed');
        }

        $guardian = $invoice->student?->guardianLinks
            ->sortByDesc('is_primary_contact')
            ->pluck('guardian')
            ->filter()
            ->first();

        $payment = $settlement->settleSimulatedTransaction($invoice->payment_reference, [
            'id' => 'SIM-TXN-'.$invoice->getKey().'-'.now()->format('YmdHis'),
            'status' => 'success',
            'reference' => $invoice->payment_reference,
            'amount' => (int) round(((float) $invoice->balance) * 100),
            'payer' => $guardian?->email ?? $guardian?->phone,
            'invoice_id' => $invoice->getKey(),
            'channel' => $validated['payment_method'],
            'payment_method' => $validated['payment_method'],
            'bank' => $validated['bank'] ?? null,
            'teller_number' => $validated['teller_number'] ?? null,
            'card_last4' => $validated['card_last4'] ?? null,
        ]);

        return redirect()
            ->to($invoice->payment_url ?: route('payments.checkout', ['reference' => $invoice->payment_reference]))
            ->with('status', $payment ? 'success' : 'unmatched');
    }
}
