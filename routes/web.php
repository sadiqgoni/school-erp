<?php

use App\Http\Controllers\DeviceEventController;
use App\Http\Controllers\MonnifyPaymentCallbackController;
use App\Http\Controllers\MonnifyWebhookController;
use App\Http\Controllers\PaymentReceiptController;
use App\Http\Controllers\PaystackPaymentCallbackController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\PortalDivisionLinkController;
use App\Http\Controllers\ReportCardPdfController;
use App\Http\Controllers\SalaryMonthReportPdfController;
use App\Http\Controllers\SalaryPostingPdfController;
use App\Http\Controllers\SimulatedPaymentCheckoutController;
use App\Http\Controllers\SimulatedPaymentCompleteController;
use App\Http\Controllers\StudentInvoicePdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->get('/student-invoices/{invoice}/pdf', StudentInvoicePdfController::class)
    ->name('student-invoices.pdf');

Route::middleware('auth')->get('/report-cards/{reportCard}/pdf', ReportCardPdfController::class)
    ->name('report-cards.pdf');

Route::middleware('auth')->get('/salary-postings/{posting}/pdf', SalaryPostingPdfController::class)
    ->name('salary-postings.pdf');

Route::middleware('auth')->get('/schools/{school}/salary-postings/pdf', SalaryMonthReportPdfController::class)
    ->name('salary-postings.month-pdf');

Route::get('/payments/paystack/callback', PaystackPaymentCallbackController::class)
    ->name('payments.paystack.callback');

Route::post('/payments/paystack/webhook', PaystackWebhookController::class)
    ->name('payments.paystack.webhook');

Route::get('/payments/monnify/callback', MonnifyPaymentCallbackController::class)
    ->name('payments.monnify.callback');

Route::post('/payments/monnify/webhook', MonnifyWebhookController::class)
    ->name('payments.monnify.webhook');

Route::post('/devices/events', DeviceEventController::class)
    ->name('devices.events');

Route::get('/payments/receipt', PaymentReceiptController::class)
    ->name('payments.receipt');

Route::get('/portal/division-hint', PortalDivisionLinkController::class)
    ->name('portal.division-hint');

Route::get('/payments/checkout', [SimulatedPaymentCheckoutController::class, 'show'])
    ->name('payments.checkout');

Route::post('/payments/complete', SimulatedPaymentCompleteController::class)
    ->name('payments.complete');

Route::get('/payments/simulated/checkout', [SimulatedPaymentCheckoutController::class, 'show'])
    ->name('payments.simulated.checkout');

Route::post('/payments/simulated/complete', SimulatedPaymentCompleteController::class)
    ->name('payments.simulated.complete');
