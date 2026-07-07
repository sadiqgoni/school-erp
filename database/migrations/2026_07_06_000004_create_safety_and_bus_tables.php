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
        Schema::create('student_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('identifier');
            $table->string('device_type', 40)->default('nfc_card');
            $table->string('label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'identifier'], 'student_devices_school_identifier_unique');
            $table->index(['school_id', 'student_id']);
        });

        Schema::create('bus_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 40)->nullable();
            $table->string('vehicle_name')->nullable();
            $table->string('plate_number', 40)->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone', 40)->nullable();
            $table->string('assistant_name')->nullable();
            $table->string('assistant_phone', 40)->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'is_active']);
        });

        Schema::create('bus_route_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bus_route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('pickup_point')->nullable();
            $table->string('drop_point')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['bus_route_id', 'student_id'], 'bus_route_student_unique');
        });

        Schema::create('student_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bus_route_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 40);
            $table->timestamp('happened_at');
            $table->string('source', 30)->default('manual');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'student_id', 'happened_at'], 'movements_school_student_time_idx');
            $table->index(['school_id', 'happened_at'], 'movements_school_time_idx');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->string('device_api_token', 80)->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('device_api_token');
        });

        Schema::dropIfExists('student_movements');
        Schema::dropIfExists('bus_route_students');
        Schema::dropIfExists('bus_routes');
        Schema::dropIfExists('student_devices');
    }
};
