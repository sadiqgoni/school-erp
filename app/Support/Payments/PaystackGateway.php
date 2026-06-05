<?php

namespace App\Support\Payments;

use App\Models\Guardian;
use App\Models\StudentInvoice;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PaystackGateway
{
    protected string $baseUrl = 'https://api.paystack.co';

    public function initialize(StudentInvoice $invoice): PaymentInitialization
    {
        $invoice->loadMissing(['student.guardianLinks.guardian', 'school']);

        $reference = $invoice->payment_reference ?: $this->makeReference($invoice);
        $response = $this->client()
            ->post("{$this->baseUrl}/transaction/initialize", [
                'amount' => (int) round(((float) $invoice->balance) * 100),
                'email' => $this->payerEmail($invoice),
                'currency' => config('services.payments.currency', 'NGN'),
                'reference' => $reference,
                'callback_url' => route('payments.paystack.callback'),
                'metadata' => [
                    'invoice_id' => $invoice->getKey(),
                    'invoice_number' => $invoice->invoice_number,
                    'school_id' => $invoice->school_id,
                    'student_id' => $invoice->student_id,
                    'student_name' => $invoice->student?->full_name,
                    'school_name' => $invoice->school?->name,
                ],
            ]);

        if ($response->failed() || ! $response->json('status')) {
            throw new RuntimeException($response->json('message') ?: 'Unable to initialize Paystack payment.');
        }

        return new PaymentInitialization(
            provider: 'paystack',
            reference: (string) $response->json('data.reference', $reference),
            authorizationUrl: (string) $response->json('data.authorization_url'),
            payload: $response->json(),
        );
    }

    public function verify(string $reference): array
    {
        $response = $this->client()
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if ($response->failed() || ! $response->json('status')) {
            throw new RuntimeException($response->json('message') ?: 'Unable to verify Paystack transaction.');
        }

        return $response->json('data') ?? [];
    }

    public function isValidSignature(string $payload, ?string $signature): bool
    {
        if (blank($signature)) {
            return false;
        }

        $secret = $this->secretKey();
        $hash = hash_hmac('sha512', $payload, $secret);

        return hash_equals($hash, $signature);
    }

    protected function client(): PendingRequest
    {
        return Http::withToken($this->secretKey())
            ->acceptJson()
            ->asJson();
    }

    protected function secretKey(): string
    {
        $secret = config('services.paystack.secret_key');

        if (blank($secret)) {
            throw new RuntimeException('Paystack secret key is not configured.');
        }

        return (string) $secret;
    }

    protected function makeReference(StudentInvoice $invoice): string
    {
        return 'SCH-'.$invoice->school_id.'-INV-'.$invoice->getKey().'-'.Str::upper(Str::random(8));
    }

    protected function payerEmail(StudentInvoice $invoice): string
    {
        $guardian = $invoice->student?->guardianLinks
            ->sortByDesc('is_primary_contact')
            ->pluck('guardian')
            ->filter(fn (?Guardian $guardian): bool => filled($guardian?->email))
            ->first();

        return $guardian?->email
            ?? $invoice->student?->email
            ?? $invoice->school?->email
            ?? config('mail.from.address');
    }
}
