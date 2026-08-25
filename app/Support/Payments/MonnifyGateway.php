<?php

namespace App\Support\Payments;

use App\Models\Guardian;
use App\Models\StudentInvoice;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MonnifyGateway
{
    public function initialize(StudentInvoice $invoice): PaymentInitialization
    {
        $invoice->loadMissing(['student.guardianLinks.guardian', 'school']);

        $reference = $invoice->payment_reference ?: $this->makeReference($invoice);

        $response = $this->client()
            ->post("{$this->baseUrl()}/api/v1/merchant/transactions/init-transaction", [
                'amount' => round((float) $invoice->balance, 2),
                'customerName' => $this->payerName($invoice),
                'customerEmail' => $this->payerEmail($invoice),
                'paymentReference' => $reference,
                'paymentDescription' => 'Invoice '.$invoice->invoice_number,
                'currencyCode' => config('services.payments.currency', 'NGN'),
                'contractCode' => $this->contractCode(),
                'redirectUrl' => route('payments.monnify.callback', ['reference' => $reference]),
                'paymentMethods' => ['CARD', 'ACCOUNT_TRANSFER'],
                'metadata' => [
                    'invoice_id' => $invoice->getKey(),
                    'invoice_number' => $invoice->invoice_number,
                    'school_id' => $invoice->school_id,
                    'student_id' => $invoice->student_id,
                    'student_name' => $invoice->student?->full_name,
                    'school_name' => $invoice->school?->name,
                ],
            ]);

        if ($response->failed() || ! $response->json('requestSuccessful')) {
            throw new RuntimeException($response->json('responseMessage') ?: 'Unable to initialize Monnify payment.');
        }

        return new PaymentInitialization(
            provider: 'monnify',
            reference: (string) $response->json('responseBody.paymentReference', $reference),
            authorizationUrl: (string) $response->json('responseBody.checkoutUrl'),
            payload: $response->json(),
        );
    }

    public function verify(string $reference): array
    {
        $response = $this->client()
            ->get("{$this->baseUrl()}/api/v2/merchant/transactions/query", [
                'paymentReference' => $reference,
            ]);

        if ($response->failed() || ! $response->json('requestSuccessful')) {
            throw new RuntimeException($response->json('responseMessage') ?: 'Unable to verify Monnify transaction.');
        }

        return $response->json('responseBody') ?? [];
    }

    public function isValidSignature(string $payload, ?string $signature): bool
    {
        if (blank($signature)) {
            return false;
        }

        $hash = hash_hmac('sha512', $payload, $this->secretKey());

        return hash_equals($hash, $signature);
    }

    protected function client(): PendingRequest
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson();
    }

    /**
     * Monnify access tokens last ~1 hour. Cache it (with a safety margin)
     * instead of authenticating on every single request.
     */
    protected function accessToken(): string
    {
        return Cache::remember(
            'monnify:access-token:'.$this->apiKey(),
            now()->addMinutes(50),
            function (): string {
                $response = Http::withBasicAuth($this->apiKey(), $this->secretKey())
                    ->acceptJson()
                    ->post("{$this->baseUrl()}/api/v1/auth/login");

                if ($response->failed() || ! $response->json('requestSuccessful')) {
                    throw new RuntimeException($response->json('responseMessage') ?: 'Unable to authenticate with Monnify.');
                }

                $token = $response->json('responseBody.accessToken');

                if (blank($token)) {
                    throw new RuntimeException('Monnify did not return an access token.');
                }

                return (string) $token;
            },
        );
    }

    protected function baseUrl(): string
    {
        return rtrim((string) config('services.monnify.base_url', 'https://sandbox.monnify.com'), '/');
    }

    protected function apiKey(): string
    {
        $key = config('services.monnify.api_key');

        if (blank($key)) {
            throw new RuntimeException('Monnify API key is not configured.');
        }

        return (string) $key;
    }

    protected function secretKey(): string
    {
        $secret = config('services.monnify.secret_key');

        if (blank($secret)) {
            throw new RuntimeException('Monnify secret key is not configured.');
        }

        return (string) $secret;
    }

    protected function contractCode(): string
    {
        $code = config('services.monnify.contract_code');

        if (blank($code)) {
            throw new RuntimeException('Monnify contract code is not configured.');
        }

        return (string) $code;
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

    protected function payerName(StudentInvoice $invoice): string
    {
        $guardian = $invoice->student?->guardianLinks
            ->sortByDesc('is_primary_contact')
            ->pluck('guardian')
            ->filter(fn (?Guardian $guardian): bool => filled($guardian?->name))
            ->first();

        return $guardian?->name
            ?? $invoice->student?->full_name
            ?? 'Parent/Guardian';
    }
}
