<?php

namespace Tests\Feature;

use App\Models\CommunicationLog;
use App\Models\FeePayment;
use App\Models\StudentInvoice;
use App\Support\Payments\PaystackGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaystackPaymentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_paystack_gateway_initializes_invoice_checkout_link(): void
    {
        $this->seed();
        config()->set('services.paystack.secret_key', 'sk_test_demo');

        $invoice = StudentInvoice::query()
            ->where('invoice_number', 'INV-2026-0001')
            ->firstOrFail();

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'message' => 'Authorization URL created',
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/demo',
                    'access_code' => 'access-demo',
                    'reference' => 'SCH-1-INV-1-DEMO',
                ],
            ]),
        ]);

        $initialization = app(PaystackGateway::class)->initialize($invoice);

        $this->assertSame('paystack', $initialization->provider);
        $this->assertSame('SCH-1-INV-1-DEMO', $initialization->reference);
        $this->assertSame('https://checkout.paystack.com/demo', $initialization->authorizationUrl);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.paystack.co/transaction/initialize'
            && $request['amount'] === 2500000
            && $request['email'] === 'guardian@example.com'
            && $request['currency'] === 'NGN');
    }

    public function test_paystack_webhook_settles_successful_payment_once(): void
    {
        $this->seed();
        config()->set('services.paystack.secret_key', 'sk_test_demo');

        $invoice = StudentInvoice::query()
            ->where('invoice_number', 'INV-2026-0001')
            ->firstOrFail();

        $invoice->forceFill([
            'payment_provider' => 'paystack',
            'payment_reference' => 'PAYSTACK-REF-001',
            'payment_url' => 'https://checkout.paystack.com/demo',
            'payment_status' => 'initialized',
        ])->save();

        Http::fake([
            'api.paystack.co/transaction/verify/PAYSTACK-REF-001' => Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'id' => 123456789,
                    'status' => 'success',
                    'reference' => 'PAYSTACK-REF-001',
                    'amount' => 2500000,
                    'paidAt' => '2026-09-10T10:15:00.000Z',
                    'customer' => [
                        'email' => 'guardian@example.com',
                    ],
                    'authorization' => [
                        'channel' => 'card',
                    ],
                ],
            ]),
        ]);

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'PAYSTACK-REF-001',
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha512', $payload, 'sk_test_demo');

        $this->postJson('/payments/paystack/webhook', json_decode($payload, true), [
            'x-paystack-signature' => $signature,
        ])->assertNoContent();

        $this->postJson('/payments/paystack/webhook', json_decode($payload, true), [
            'x-paystack-signature' => $signature,
        ])->assertNoContent();

        $this->assertDatabaseCount(FeePayment::class, 2);
        $this->assertDatabaseHas(FeePayment::class, [
            'student_invoice_id' => $invoice->getKey(),
            'payment_provider' => 'paystack',
            'reference' => 'PAYSTACK-REF-001',
            'amount' => 25000,
            'status' => 'confirmed',
        ]);
        $this->assertDatabaseHas(StudentInvoice::class, [
            'id' => $invoice->getKey(),
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseHas(CommunicationLog::class, [
            'student_id' => $invoice->student_id,
            'event_type' => 'fee_payment_received',
            'channel' => 'sms',
            'recipient_contact' => '+2348011111111',
        ]);
    }
}
