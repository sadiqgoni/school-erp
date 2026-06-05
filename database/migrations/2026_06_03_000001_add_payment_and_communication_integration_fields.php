<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_invoices', function (Blueprint $table): void {
            $table->string('payment_provider', 40)->nullable()->after('status');
            $table->string('payment_reference')->nullable()->after('payment_provider');
            $table->string('payment_url')->nullable()->after('payment_reference');
            $table->string('payment_status', 40)->default('not_initialized')->after('payment_url');
            $table->json('payment_metadata')->nullable()->after('payment_status');

            $table->index(['school_id', 'payment_provider', 'payment_reference'], 'student_inv_payment_provider_ref_idx');
        });

        Schema::table('fee_payments', function (Blueprint $table): void {
            $table->string('payment_provider', 40)->nullable()->after('payment_method');
            $table->string('provider_transaction_id')->nullable()->after('payment_provider');
            $table->json('provider_payload')->nullable()->after('provider_transaction_id');

            $table->index(['school_id', 'payment_provider', 'provider_transaction_id'], 'fee_payment_provider_txn_idx');
        });

        Schema::table('communication_logs', function (Blueprint $table): void {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->after('school_id')->constrained()->nullOnDelete();
            $table->foreignId('guardian_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->after('guardian_id')->constrained()->nullOnDelete();
            $table->nullableMorphs('related');
            $table->string('event_type', 80)->nullable();
            $table->string('channel', 40)->default('in_app');
            $table->string('direction', 20)->default('outbound');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_contact')->nullable();
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('status', 30)->default('queued');
            $table->string('provider', 40)->nullable();
            $table->string('provider_message_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();

            $table->index(['school_id', 'event_type', 'status'], 'communication_school_event_status_idx');
            $table->index(['school_id', 'channel', 'created_at'], 'communication_school_channel_created_idx');
        });

        Schema::table('reminders', function (Blueprint $table): void {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_invoice_id')->nullable()->after('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->after('student_invoice_id')->constrained()->nullOnDelete();
            $table->foreignId('guardian_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
            $table->string('type', 80)->default('fee_due');
            $table->string('channel', 40)->default('sms');
            $table->string('recipient_contact')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('status', 30)->default('pending');
            $table->json('metadata')->nullable();

            $table->index(['school_id', 'type', 'status'], 'reminders_school_type_status_idx');
            $table->index(['school_id', 'scheduled_for'], 'reminders_school_scheduled_idx');
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table): void {
            $table->dropIndex('reminders_school_scheduled_idx');
            $table->dropIndex('reminders_school_type_status_idx');
            $table->dropConstrainedForeignId('guardian_id');
            $table->dropConstrainedForeignId('student_id');
            $table->dropConstrainedForeignId('student_invoice_id');
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn([
                'type',
                'channel',
                'recipient_contact',
                'message',
                'scheduled_for',
                'sent_at',
                'status',
                'metadata',
            ]);
        });

        Schema::table('communication_logs', function (Blueprint $table): void {
            $table->dropIndex('communication_school_channel_created_idx');
            $table->dropIndex('communication_school_event_status_idx');
            $table->dropMorphs('related');
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('guardian_id');
            $table->dropConstrainedForeignId('student_id');
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn([
                'event_type',
                'channel',
                'direction',
                'recipient_name',
                'recipient_contact',
                'subject',
                'body',
                'status',
                'provider',
                'provider_message_id',
                'metadata',
                'sent_at',
                'delivered_at',
                'read_at',
                'failed_at',
                'failure_reason',
            ]);
        });

        Schema::table('fee_payments', function (Blueprint $table): void {
            $table->dropIndex('fee_payment_provider_txn_idx');
            $table->dropColumn([
                'payment_provider',
                'provider_transaction_id',
                'provider_payload',
            ]);
        });

        Schema::table('student_invoices', function (Blueprint $table): void {
            $table->dropIndex('student_inv_payment_provider_ref_idx');
            $table->dropColumn([
                'payment_provider',
                'payment_reference',
                'payment_url',
                'payment_status',
                'payment_metadata',
            ]);
        });
    }
};
