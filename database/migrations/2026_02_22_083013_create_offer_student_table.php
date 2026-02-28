<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_student', function (Blueprint $table) {
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->primary(['offer_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_student');
    }
};
