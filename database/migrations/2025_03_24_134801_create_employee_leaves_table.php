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
        Schema::create('employee_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id'); // Assuming you have an employees table
            $table->foreignId('leave_type_id'); // Assuming you have a leave_types table
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('duration', 5, 2); // Assuming duration is a decimal value (e.g., 8.5 hours)
            $table->text('details')->nullable();
            $table->boolean('isHalf')->default(false); // For half-day leave
            $table->integer('howManyVacationDay')->default(0); // Assuming it's a number representing vacation days
            $table->unsignedBigInteger('approved_by')->nullable(); // ID of the user who approved the leave
            $table->boolean('is_approve')->default(false); // For approval status
            $table->string('status')->default('pending'); // Status of the leave request (e.g., 'pending', 'approved', 'rejected')
            $table->timestamps();
            
            // Foreign key for approved_by, referencing users table (assuming it's the user who approved the leave)
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_leaves');
    }
};
