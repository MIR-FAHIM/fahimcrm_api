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
        Schema::create('contact_u_s_forms', function (Blueprint $table) {
            $table->id();
            $table->string('person_name');
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('type');
            $table->string('status');
            $table->unsignedBigInteger('campaign_id');
            $table->string('website');
            $table->text('additional_field_one')->nullable();
            $table->text('additional_field_two')->nullable();
            $table->text('query');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_u_s_forms');
    }
};
