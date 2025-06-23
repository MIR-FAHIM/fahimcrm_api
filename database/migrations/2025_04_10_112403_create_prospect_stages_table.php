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
        Schema::create('prospect_stages', function (Blueprint $table) {
            $table->id();
            $table->string('stage_name');
            $table->boolean('is_active')->default(true);
            $table->string('color_code')->nullable(); // example: #FF5733
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospect_stages');
    }
};
