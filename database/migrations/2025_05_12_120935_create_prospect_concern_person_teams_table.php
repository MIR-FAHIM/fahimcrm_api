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
        Schema::create('prospect_concern_person_teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prospect_id');
            $table->unsignedBigInteger('employee_id');
            $table->boolean('is_active')->default(true);
            $table->boolean('notify')->default(false);
            $table->timestamps();
    
            // Optional: Add foreign key constraints if applicable
            // $table->foreign('prospect_id')->references('id')->on('prospects')->onDelete('cascade');
            // $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospect_concern_person_teams');
    }
};
