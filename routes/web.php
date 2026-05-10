<?php

use App\Http\Controllers\ReportCardPdfController;
use App\Http\Controllers\StudentInvoicePdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->get('/student-invoices/{invoice}/pdf', StudentInvoicePdfController::class)
    ->name('student-invoices.pdf');

Route::middleware('auth')->get('/report-cards/{reportCard}/pdf', ReportCardPdfController::class)
    ->name('report-cards.pdf');
