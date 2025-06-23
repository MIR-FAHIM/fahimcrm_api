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
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->string('prospect_name');
            $table->boolean('is_individual')->default(true);
            $table->unsignedBigInteger('industry_type_id')->nullable();
            $table->unsignedBigInteger('interested_for_id')->nullable();
            $table->unsignedBigInteger('information_source_id')->nullable();
            $table->unsignedBigInteger('stage_id')->nullable();
            $table->unsignedBigInteger('priority_id')->nullable();
            $table->unsignedBigInteger('status')->nullable();
            $table->unsignedBigInteger('is_active')->nullable();
            $table->unsignedBigInteger('is_opportunity')->nullable();
            $table->string('website_link')->nullable();
            $table->string('facebook_page')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('address')->nullable();
            $table->text('note')->nullable();
            $table->text('last_activity')->timestamps()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
