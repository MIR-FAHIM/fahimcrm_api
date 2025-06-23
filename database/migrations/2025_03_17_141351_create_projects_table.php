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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_name'); // Adding the project_name column
            $table->foreignId('department_id')->constrained()->onDelete('cascade'); // Foreign key to the departments table
            $table->boolean('is_tech')->default(false); // Flag to indicate if the project is related to technology
            $table->boolean('is_marketing')->default(false); // Flag to indicate if the project is related to marketing
            $table->text('description')->nullable(); // Description of the project
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
