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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_title'); // Adding the task_title column
            $table->text('task_details')->nullable(); // Adding task_details column (nullable for long descriptions)
            $table->foreignId('priority_id')->constrained('priorities')->onDelete('cascade'); // Foreign key to the priorities table
            $table->foreignId('task_type_id')->constrained('task_types')->onDelete('cascade'); // Foreign key to the task_types table
            $table->boolean('is_remind')->default(false); // Whether the task should remind (default false)
            $table->date('due_date')->nullable(); // Due date for the task (nullable)
            $table->integer('completion_percentage')->nullable(); // Due date for the task (nullable)
            $table->boolean('show_completion_percentage')->default(false); // Due date for the task (nullable)
            $table->foreignId('project_id')->nullable()->onDelete('cascade'); // Foreign key to the projects table
            $table->foreignId('project_phase_id')->nullable(); // Foreign key to the projects table
            $table->foreignId('prospect_id')->nullable(); // Foreign key to the projects table
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Foreign key to the users table (creator)
            $table->foreignId('status_id')->constrained('task_statuses')->onDelete('cascade'); // Foreign key to the task_statuses table
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade'); // Foreign key to the departments table
  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
