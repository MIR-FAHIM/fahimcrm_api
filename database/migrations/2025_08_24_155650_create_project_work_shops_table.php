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
        Schema::create('project_work_shops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('created_by'); // user_id as created_by
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('url')->nullable();
            $table->string('type')->nullable();
            $table->string('status', 50)->default('pending');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_work_shops');
    }
};
