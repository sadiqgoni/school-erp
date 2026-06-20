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
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('guardian_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
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
            $table->timestamps();

            $table->index(['school_id', 'event_type', 'status'], 'communication_school_event_status_idx');
            $table->index(['school_id', 'channel', 'created_at'], 'communication_school_channel_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
