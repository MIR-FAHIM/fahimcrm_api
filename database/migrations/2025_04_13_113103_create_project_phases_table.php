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
        Schema::create('project_phases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('phase_name');
            $table->integer('phase_order_id')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // You can define your own statuses (e.g., pending, in_progress, completed)
            $table->integer('priority')->nullable(); // Higher number means higher priority (if needed)
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->decimal('phase_completion_percentage', 5, 2)->default(0); // e.g., 87.50%
            $table->timestamps();

            // Optional: foreign key constraint
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_phases');
    }
};
