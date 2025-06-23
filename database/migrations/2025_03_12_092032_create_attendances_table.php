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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('check_in_time')->nullable();
            $table->string('check_in_location')->nullable();
            $table->boolean('is_late')->default(false);
            $table->boolean('is_work_from_home')->default(false);
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lon', 10, 7)->nullable();
            $table->timestamp('check_out_time')->nullable();
            $table->decimal('check_out_lat', 10, 7)->nullable();
            $table->decimal('check_out_lon', 10, 7)->nullable();
            $table->string('check_out_location')->nullable();
            $table->string('late_reason')->nullable();
            $table->string('early_leave_reason')->nullable();
            $table->boolean('is_early_leave')->default(false);
            $table->boolean('from_field')->default(false);
            $table->integer('total_duration')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
