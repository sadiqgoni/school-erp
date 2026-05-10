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
        Schema::create('guardian_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guardian_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('relationship', 50)->default('guardian');
            $table->boolean('is_primary_contact')->default(false);
            $table->boolean('can_pick_up')->default(true);
            $table->boolean('receives_sms')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['guardian_id', 'student_id']);
            $table->index(['school_id', 'student_id', 'is_primary_contact'], 'guardian_student_school_student_primary_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guardian_students');
    }
};
