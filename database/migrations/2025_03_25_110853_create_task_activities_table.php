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
        Schema::create('task_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id'); // Foreign key for task_id
            $table->string('activity_title'); // Title of the activity
            $table->text('activity_details'); // Details of the activity
            $table->string('status'); // Status of the activity
            $table->string('type'); // Type of the activity
            $table->unsignedBigInteger('created_by'); // User who created the activity
            $table->timestamps();

            // Foreign key constraint (if task table exists)
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_activities');
    }
};
