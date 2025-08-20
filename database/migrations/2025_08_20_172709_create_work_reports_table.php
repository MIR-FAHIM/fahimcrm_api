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
        Schema::create('work_reports', function (Blueprint $table) {
            $table->id();
            
            // The ID of the user who submitted the report.
            $table->unsignedBigInteger('user_id');
            
            // The full text of the work report.
            $table->longText('report_text');
            
            // The date the report was submitted.
            $table->date('report_date');
            
            // The type of report (e.g., 'daily', 'weekly').
            $table->string('type')->default('daily');

            // A flag to indicate if the report is active or not.
            $table->boolean('is_active')->default(true);

            // Automatically adds 'created_at' and 'updated_at' columns.
            $table->timestamps();

            // Optional: Add a foreign key constraint for the user ID.
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_reports');
    }
};