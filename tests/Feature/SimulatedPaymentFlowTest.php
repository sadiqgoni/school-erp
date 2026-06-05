<?php

namespace Tests\Feature;

use App\Models\CommunicationLog;
use App\Models\FeePayment;
use App\Models\StudentInvoice;
use App\Support\Payments\SimulatedPaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulatedPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulated_payment_link_can_be_completed_successfully(): void
    {
        $this->seed();

        $invoice = StudentInvoice::query()
            ->where('invoice_number', 'INV-2026-0001')
            ->firstOrFail();

        $initialization = app(SimulatedPaymentGateway::class)->initialize($invoice);

        $invoice->forceFill([
            'payment_provider' => $initialization->provider,
            'payment_reference' => $initialization->reference,
            'payment_url' => $initialization->authorizationUrl,
            'payment_status' => 'initialized',
            'payment_metadata' => $initialization->payload,
        ])->save();

        $this->get($invoice->payment_url)
            ->assertOk()
            ->assertSee('Complete payment')
            ->assertSee('Card')
            ->assertSee('Bank / Teller')
            ->assertSee('Online Banking')
            ->assertSee($invoice->invoice_number);

        $this->post('/payments/simulated/complete', [
            'reference' => $invoice->payment_reference,
            'outcome' => 'success',
            'payment_method' => 'card',
        ])->assertRedirect($invoice->payment_url);

        $this->assertDatabaseCount(FeePayment::class, 2);
        $this->assertDatabaseHas(FeePayment::class, [
            'student_invoice_id' => $invoice->getKey(),
            'payment_provider' => 'simulated',
            'reference' => $invoice->payment_reference,
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

    public function test_simulated_payment_link_can_mark_gateway_failure_without_creating_payment(): void
    {
        $this->seed();

        $invoice = StudentInvoice::query()
            ->where('invoice_number', 'INV-2026-0001')
            ->firstOrFail();

        $initialization = app(SimulatedPaymentGateway::class)->initialize($invoice);

        $invoice->forceFill([
            'payment_provider' => $initialization->provider,
            'payment_reference' => $initialization->reference,
            'payment_url' => $initialization->authorizationUrl,
            'payment_status' => 'initialized',
            'payment_metadata' => $initialization->payload,
        ])->save();

        $this->post('/payments/simulated/complete', [
            'reference' => $invoice->payment_reference,
            'outcome' => 'failed',
            'payment_method' => 'online_banking',
        ])->assertRedirect($invoice->payment_url);

        $this->assertDatabaseCount(FeePayment::class, 1);
        $this->assertDatabaseHas(StudentInvoice::class, [
            'id' => $invoice->getKey(),
            'status' => 'partial',
            'payment_status' => 'failed',
        ]);
    }
}
