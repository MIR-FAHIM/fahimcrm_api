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
        Schema::create('task_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade'); // Foreign key to tasks table
            $table->string('followup_title');
            $table->text('followup_details');
            $table->string('type'); // Followup type (e.g., comment, reminder, etc.)
            $table->string('status'); // Status of the follow-up (e.g., active, completed)
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Foreign key to users table (creator)
            $table->timestamps(); // Created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_followups');
    }
};
