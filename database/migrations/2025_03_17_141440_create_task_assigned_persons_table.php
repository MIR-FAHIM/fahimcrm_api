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
        Schema::create('task_assigned_persons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assigned_person')->constrained('users')->onDelete('cascade'); // Assuming assigned_person refers to a user in the 'users' table
            $table->foreignId('assigned_by'); // Assuming assigned_person refers to a user in the 'users' table
            $table->boolean('is_main')->default(false); // To indicate if the person is the main person for the task
            $table->foreignId('task_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_assigned_persons');
    }
};
