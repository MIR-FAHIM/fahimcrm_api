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
        Schema::create('project_features', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id'); // relation to projects table

            $table->string('feature_name');                // e.g. "Chat Integration"
            $table->text('description')->nullable();       // detailed explanation
            $table->string('type')->nullable();            // e.g. "Service", "Module", "Addon"
            $table->enum('status', ['planned', 'in_progress', 'completed', 'deprecated'])
                  ->default('planned');                    // feature lifecycle
            $table->unsignedTinyInteger('completion_percentage')
                  ->default(0);                            // 0–100 progress
            $table->string('version')->nullable();         // release version tag
            $table->text('note')->nullable();              // internal notes
            $table->text('next_plan')->nullable();         // next steps / roadmap

            $table->timestamps();

            // Foreign key to projects table
            $table->foreign('project_id')
                  ->references('id')->on('projects')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_features');
    }
};
