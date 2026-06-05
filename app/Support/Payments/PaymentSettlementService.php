<?php

namespace App\Support\Payments;

use App\Models\FeePayment;
use App\Models\StudentInvoice;
use App\Support\PaymentCommunicationCoordinator;
use Illuminate\Support\Facades\DB;

class PaymentSettlementService
{
    public function settlePaystackTransaction(array $transaction): ?FeePayment
    {
        if (($transaction['status'] ?? null) !== 'success') {
            return null;
        }

        $reference = (string) ($transaction['reference'] ?? '');

        if (blank($reference)) {
            return null;
        }

        return $this->settleSuccessfulTransaction(
            provider: 'paystack',
            reference: $reference,
            amount: ((float) ($transaction['amount'] ?? 0)) / 100,
            payload: $transaction,
            providerTransactionId: (string) ($transaction['id'] ?? ''),
            payer: data_get($transaction, 'customer.email'),
            paymentDate: filled($transaction['paid_at'] ?? $transaction['paidAt'] ?? null)
                ? (string) ($transaction['paid_at'] ?? $transaction['paidAt'])
                : now()->toDateString(),
            notes: 'Paystack online payment',
            metadata: array_filter([
                'provider_transaction_id' => $transaction['id'] ?? null,
                'paid_at' => $transaction['paid_at'] ?? $transaction['paidAt'] ?? null,
                'channel' => data_get($transaction, 'authorization.channel'),
            ]),
        );
    }

    public function settleSimulatedTransaction(string $reference, array $payload = []): ?FeePayment
    {
        $channel = (string) ($payload['channel'] ?? $payload['payment_method'] ?? 'card');
        $methodLabel = match ($channel) {
            'bank_transfer' => 'Bank transfer / teller',
            'online_banking' => 'Online banking',
            default => 'Card payment',
        };

        return $this->settleSuccessfulTransaction(
            provider: 'simulated',
            reference: $reference,
            amount: ((float) ($payload['amount'] ?? 0)) / 100,
            payload: $payload,
            providerTransactionId: (string) ($payload['id'] ?? 'SIM-'.now()->format('YmdHis')),
            payer: $payload['payer'] ?? null,
            paymentDate: now()->toDateString(),
            notes: $methodLabel.' checkout payment',
            metadata: array_filter([
                'provider_transaction_id' => $payload['id'] ?? null,
                'paid_at' => now()->toIso8601String(),
                'channel' => $channel,
            ]),
        );
    }

    protected function settleSuccessfulTransaction(
        string $provider,
        string $reference,
        float $amount,
        array $payload,
        string $providerTransactionId,
        ?string $payer,
        string $paymentDate,
        string $notes,
        array $metadata = [],
    ): ?FeePayment {
        if (blank($reference) || $amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($provider, $reference, $amount, $payload, $providerTransactionId, $payer, $paymentDate, $notes, $metadata): ?FeePayment {
            $invoice = StudentInvoice::query()
                ->where('payment_provider', $provider)
                ->where('payment_reference', $reference)
                ->lockForUpdate()
                ->first();

            if (! $invoice) {
                return null;
            }

            $payment = FeePayment::query()
                ->where('school_id', $invoice->school_id)
                ->where('payment_provider', $provider)
                ->where('reference', $reference)
                ->first();

            if ($payment) {
                return $payment;
            }

            $payment = FeePayment::query()->create([
                'school_id' => $invoice->school_id,
                'student_invoice_id' => $invoice->getKey(),
                'student_id' => $invoice->student_id,
                'payer' => $payer,
                'payment_date' => $paymentDate,
                'amount' => $amount,
                'payment_method' => 'online',
                'payment_provider' => $provider,
                'provider_transaction_id' => $providerTransactionId,
                'provider_payload' => $payload,
                'income_account_id' => $invoice->income_account_id,
                'reference' => $reference,
                'status' => 'confirmed',
                'notes' => $notes,
            ]);

            $invoice->forceFill([
                'payment_status' => 'paid',
                'payment_metadata' => $metadata,
            ])->saveQuietly();

            app(PaymentCommunicationCoordinator::class)
                ->queuePaymentConfirmation($payment);

            return $payment;
        });
    }
}
