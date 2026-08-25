<?php

namespace Tests\Feature;

use App\Filament\Resources\ParentInvoices\Tables\ParentInvoicesTable;
use App\Models\CommunicationLog;
use App\Models\FeePayment;
use App\Models\StudentInvoice;
use App\Support\Payments\MonnifyGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonnifyPaymentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function configureMonnify(): void
    {
        config()->set('services.monnify.base_url', 'https://sandbox.monnify.com');
        config()->set('services.monnify.api_key', 'MK_TEST_DEMO');
        config()->set('services.monnify.secret_key', 'sk_test_demo_secret');
        config()->set('services.monnify.contract_code', '1234567890');
    }

    protected function fakeMonnifyLogin(): void
    {
        Http::fake([
            'sandbox.monnify.com/api/v1/auth/login' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => [
                    'accessToken' => 'demo-access-token',
                    'expiresIn' => 3300,
                ],
            ]),
        ]);
    }

    public function test_monnify_gateway_initializes_invoice_checkout_link(): void
    {
        $this->seed();
        $this->configureMonnify();

        $invoice = StudentInvoice::query()
            ->where('invoice_number', 'INV-2026-0001')
            ->firstOrFail();

        Http::fake([
            'sandbox.monnify.com/api/v1/auth/login' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => ['accessToken' => 'demo-access-token'],
            ]),
            'sandbox.monnify.com/api/v1/merchant/transactions/init-transaction' => Http::response([
                'requestSuccessful' => true,
                'responseMessage' => 'success',
                'responseBody' => [
                    'checkoutUrl' => 'https://sandbox.monnify.com/checkout/demo',
                    'paymentReference' => 'SCH-1-INV-1-DEMO',
                ],
            ]),
        ]);

        $initialization = app(MonnifyGateway::class)->initialize($invoice);

        $this->assertSame('monnify', $initialization->provider);
        $this->assertSame('SCH-1-INV-1-DEMO', $initialization->reference);
        $this->assertSame('https://sandbox.monnify.com/checkout/demo', $initialization->authorizationUrl);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://sandbox.monnify.com/api/v1/merchant/transactions/init-transaction'
            && $request['amount'] === 25000.0
            && $request['customerEmail'] === 'guardian@example.com'
            && $request['currencyCode'] === 'NGN'
            && $request['contractCode'] === '1234567890'
            && str_starts_with((string) $request['redirectUrl'], route('payments.monnify.callback'))
            && str_contains((string) $request['redirectUrl'], 'reference='));
    }

    public function test_monnify_access_token_is_cached_between_calls(): void
    {
        $this->seed();
        $this->configureMonnify();
        Cache::flush();

        $invoice = StudentInvoice::query()
            ->where('invoice_number', 'INV-2026-0001')
            ->firstOrFail();

        Http::fake([
            'sandbox.monnify.com/api/v1/auth/login' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => ['accessToken' => 'demo-access-token'],
            ]),
            'sandbox.monnify.com/api/v1/merchant/transactions/init-transaction' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => [
                    'checkoutUrl' => 'https://sandbox.monnify.com/checkout/demo',
                    'paymentReference' => 'SCH-1-INV-1-DEMO',
                ],
            ]),
        ]);

        $gateway = app(MonnifyGateway::class);
        $gateway->initialize($invoice);

        $invoice->forceFill(['payment_reference' => null])->save();
        $gateway->initialize($invoice);

        Http::assertSentCount(3);
    }

    public function test_parent_invoice_payment_link_uses_configured_monnify_provider(): void
    {
        $this->seed();
        config()->set('services.payments.default', 'monnify');
        $this->configureMonnify();

        $invoice = StudentInvoice::query()
            ->where('invoice_number', 'INV-2026-0001')
            ->firstOrFail();

        Http::fake([
            'sandbox.monnify.com/api/v1/auth/login' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => ['accessToken' => 'demo-access-token'],
            ]),
            'sandbox.monnify.com/api/v1/merchant/transactions/init-transaction' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => [
                    'checkoutUrl' => 'https://sandbox.monnify.com/checkout/parent-demo',
                    'paymentReference' => 'SCH-1-INV-1-PARENT',
                ],
            ]),
        ]);

        $method = new \ReflectionMethod(ParentInvoicesTable::class, 'paymentUrl');
        $url = $method->invoke(null, $invoice);

        $this->assertSame('https://sandbox.monnify.com/checkout/parent-demo', $url);
        $this->assertDatabaseHas(StudentInvoice::class, [
            'id' => $invoice->getKey(),
            'payment_provider' => 'monnify',
            'payment_reference' => 'SCH-1-INV-1-PARENT',
            'payment_url' => 'https://sandbox.monnify.com/checkout/parent-demo',
            'payment_status' => 'initialized',
        ]);
    }

    public function test_monnify_webhook_settles_successful_payment_once(): void
    {
        $this->seed();
        $this->configureMonnify();

        $invoice = StudentInvoice::query()
            ->where('invoice_number', 'INV-2026-0001')
            ->firstOrFail();

        $invoice->forceFill([
            'payment_provider' => 'monnify',
            'payment_reference' => 'MONNIFY-REF-001',
            'payment_url' => 'https://sandbox.monnify.com/checkout/demo',
            'payment_status' => 'initialized',
        ])->save();

        Http::fake([
            'sandbox.monnify.com/api/v1/auth/login' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => ['accessToken' => 'demo-access-token'],
            ]),
            'sandbox.monnify.com/api/v2/merchant/transactions/query*' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => [
                    'transactionReference' => 'MNFY|20260910|000123',
                    'paymentReference' => 'MONNIFY-REF-001',
                    'amountPaid' => 25000,
                    'paymentStatus' => 'PAID',
                    'paidOn' => '2026-09-10 10:15:00',
                    'paymentMethod' => 'CARD',
                    'customer' => [
                        'email' => 'guardian@gmail.com',
                    ],
                ],
            ]),
        ]);

        $payload = json_encode([
            'eventType' => 'SUCCESSFUL_TRANSACTION',
            'eventData' => [
                'paymentReference' => 'MONNIFY-REF-001',
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha512', $payload, 'sk_test_demo_secret');

        $this->postJson('/payments/monnify/webhook', json_decode($payload, true), [
            'monnify-signature' => $signature,
        ])->assertNoContent();

        $this->postJson('/payments/monnify/webhook', json_decode($payload, true), [
            'monnify-signature' => $signature,
        ])->assertNoContent();

        $this->assertDatabaseCount(FeePayment::class, 2);
        $this->assertDatabaseHas(FeePayment::class, [
            'student_invoice_id' => $invoice->getKey(),
            'payment_provider' => 'monnify',
            'reference' => 'MONNIFY-REF-001',
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

    public function test_monnify_webhook_rejects_invalid_signature(): void
    {
        $this->seed();
        $this->configureMonnify();

        $countBefore = FeePayment::query()->count();

        $payload = json_encode([
            'eventType' => 'SUCCESSFUL_TRANSACTION',
            'eventData' => ['paymentReference' => 'MONNIFY-REF-BAD'],
        ], JSON_THROW_ON_ERROR);

        $this->postJson('/payments/monnify/webhook', json_decode($payload, true), [
            'monnify-signature' => 'not-a-valid-signature',
        ])->assertUnauthorized();

        $this->assertSame($countBefore, FeePayment::query()->count());
    }

    public function test_monnify_callback_redirects_to_receipt_after_successful_payment(): void
    {
        $this->seed();
        $this->configureMonnify();

        $invoice = StudentInvoice::query()
            ->where('invoice_number', 'INV-2026-0001')
            ->firstOrFail();

        $invoice->forceFill([
            'payment_provider' => 'monnify',
            'payment_reference' => 'MONNIFY-CALLBACK-001',
            'payment_url' => 'https://sandbox.monnify.com/checkout/demo-callback',
            'payment_status' => 'initialized',
        ])->save();

        Http::fake([
            'sandbox.monnify.com/api/v1/auth/login' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => ['accessToken' => 'demo-access-token'],
            ]),
            'sandbox.monnify.com/api/v2/merchant/transactions/query*' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => [
                    'transactionReference' => 'MNFY|20260910|000456',
                    'paymentReference' => 'MONNIFY-CALLBACK-001',
                    'amountPaid' => 25000,
                    'paymentStatus' => 'PAID',
                    'paidOn' => '2026-09-10 10:15:00',
                    'paymentMethod' => 'CARD',
                    'customer' => ['email' => 'guardian@example.com'],
                ],
            ]),
        ]);

        $this
            ->get('/payments/monnify/callback?reference=MONNIFY-CALLBACK-001')
            ->assertRedirect(route('payments.receipt', [
                'reference' => 'MONNIFY-CALLBACK-001',
                'status' => 'success',
            ]));

        $this->assertDatabaseHas(StudentInvoice::class, [
            'id' => $invoice->getKey(),
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);
    }
}
