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
    Schema::create('prospect_stage_change_logs', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('prospect_id');
        $table->string('old_stage')->nullable();
        $table->string('new_stage');
        $table->unsignedBigInteger('changed_by')->nullable(); // optional: user ID who made the change
        $table->timestamps();

        // Foreign key constraints (optional)
        $table->foreign('prospect_id')->references('id')->on('prospects')->onDelete('cascade');
        $table->foreign('changed_by')->references('id')->on('users')->onDelete('set null');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospect_stage_change_logs');
    }
};
