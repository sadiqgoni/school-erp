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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('student_invoice_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('guardian_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 80)->default('fee_due');
            $table->string('channel', 40)->default('sms');
            $table->string('recipient_contact')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('status', 30)->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'type', 'status'], 'reminders_school_type_status_idx');
            $table->index(['school_id', 'scheduled_for'], 'reminders_school_scheduled_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
