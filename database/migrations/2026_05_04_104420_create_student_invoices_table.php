<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_discount_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('income_account_id')->nullable()->constrained('ledger_accounts')->nullOnDelete();
            $table->string('invoice_number', 60);
            $table->string('invoice_type', 30)->default('standard');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('status', 30)->default('unpaid');
            $table->string('payment_provider', 40)->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('payment_url')->nullable();
            $table->string('payment_status', 40)->default('not_initialized');
            $table->json('payment_metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'invoice_number'], 'student_inv_school_number_unique');
            $table->index(['school_id', 'student_id', 'status'], 'student_inv_school_student_status_idx');
            $table->index(['school_id', 'payment_provider', 'payment_reference'], 'student_inv_payment_provider_ref_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_invoices');
    }
};
