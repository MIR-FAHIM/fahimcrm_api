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
        Schema::create('meeting_sets', function (Blueprint $table) {
            $table->id();
            $table->string('meeting_title');
            $table->text('meeting_context')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->unsignedBigInteger('assign_to')->nullable();
            $table->unsignedBigInteger('prospect_id')->nullable();
            $table->string('meeting_type')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('notify_time')->nullable();
            $table->string('status')->default('pending');
            $table->string('meeting_with')->nullable();
            $table->unsignedBigInteger('priority_id')->nullable();
            $table->timestamps();

            // Optional: Add foreign keys if needed
            // $table->foreign('task_id')->references('id')->on('tasks')->onDelete('set null');
            // $table->foreign('assign_to')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('prospect_id')->references('id')->on('prospects')->onDelete('set null');
            // $table->foreign('priority_id')->references('id')->on('priorities')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_sets');
    }
};
