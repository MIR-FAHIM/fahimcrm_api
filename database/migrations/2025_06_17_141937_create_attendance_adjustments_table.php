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
        Schema::create('attendance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('requested_time')->nullable();
            $table->string('type'); // e.g., 'in', 'out', 'full_day', etc.
            $table->string('status')->default('pending'); // e.g., 'pending', 'approved', 'rejected'
            $table->unsignedBigInteger('approved_by')->nullable(); // user_id of manager/HR
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->boolean('is_late')->default(false);
            $table->boolean('is_early')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_adjustments');
    }
};
