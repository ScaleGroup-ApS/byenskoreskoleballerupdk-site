<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('status')->default('scheduled');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['instructor_id', 'starts_at', 'ends_at']);
            $table->index(['vehicle_id', 'starts_at', 'ends_at']);
            $table->index(['student_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
