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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();

            // Core fields for a sales visit
            $table->string('purpose')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('status', )->default(1);
            $table->enum('visit_type', ['Planned', 'Spontaneous'])->default('Planned');
            $table->dateTime('scheduled_at');
            $table->dateTime('actual_start_at')->nullable();
            $table->dateTime('actual_end_at')->nullable();
            $table->decimal('checkin_latitude', 10, 8)->nullable();
            $table->decimal('checkin_longitude', 11, 8)->nullable();

            // Foreign keys to existing tables
            // Using unsignedBigInteger for consistency with Laravel's `id()`
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('planner_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->unsignedBigInteger('priority_id')->nullable();

            // Foreign key constraints
            // We use `->on()` to specify the table and `->references()` for the column name
            // No onDelete('cascade') as per your request
            $table->foreign('employee_id')->references('id')->on('users');
            $table->foreign('planner_id')->references('id')->on('users');
            $table->foreign('lead_id')->references('id')->on('prospects');
            $table->foreign('zone_id')->references('id')->on('zones');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};