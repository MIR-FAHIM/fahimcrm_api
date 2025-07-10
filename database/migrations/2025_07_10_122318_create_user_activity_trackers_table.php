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
        Schema::create('user_activity_trackers', function (Blueprint $table) {
            $table->id(); // Primary key for the table

            // Add the requested columns
            $table->foreignId('user_id') // Foreign key to the 'users' table
                  ->constrained() // Creates a foreign key constraint
                  ->onDelete('cascade'); // Deletes activity records if the user is deleted

            $table->string('activity_name'); // A concise name for the activity (e.g., 'Login', 'Post Created', 'Settings Updated')
            $table->text('details')->nullable(); // More detailed information about the activity, can be null
            $table->string('type')->nullable(); // Category or type of activity (e.g., 'Auth', 'CRUD', 'System'), can be null

            // Other useful parameters for comprehensive activity tracking:
            $table->ipAddress('ip_address')->nullable(); // IP address from which the activity originated
            $table->string('user_agent', 500)->nullable(); // User agent string (browser, OS, device info)
            $table->string('url', 2048)->nullable(); // The URL where the activity occurred
            $table->string('method', 10)->nullable(); // HTTP method used (e.g., GET, POST, PUT, DELETE)

            $table->timestamps(); // Adds 'created_at' and 'updated_at' columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activity_trackers');
    }
};
