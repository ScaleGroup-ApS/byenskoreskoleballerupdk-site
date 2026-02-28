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
        Schema::table('enrollment_requests', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->after('offer_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_requests', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
        });
    }
};
