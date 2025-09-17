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
        Schema::create('task_visit_relations', function (Blueprint $table) {
            $table->id();

            // Foreign keys to link the visit and the task
            $table->unsignedBigInteger('visit_id');
            $table->unsignedBigInteger('task_id');
            
            // These fields will be updated when the visit status changes
            $table->text('note')->nullable();
            
            // The location where the visit actually happened
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->string('status')->default('Pending'); // e.g., 'Pending', 'Visited', 'Canceled'

            // Foreign key constraints
            // No onDelete('cascade') as per your request
            $table->foreign('visit_id')->references('id')->on('visits');
            $table->foreign('task_id')->references('id')->on('tasks');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_visit_relations');
    }
};