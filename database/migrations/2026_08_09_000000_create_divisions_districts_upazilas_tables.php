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
        if (!Schema::hasTable('divisions')) {
            Schema::create('divisions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('bn_name')->nullable();
                $table->string('lat')->nullable();
                $table->string('long')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('districts')) {
            Schema::create('districts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('division_id');
                $table->string('name');
                $table->string('bn_name')->nullable();
                $table->string('lat')->nullable();
                $table->string('long')->nullable();
                $table->string('website')->nullable();
                $table->timestamps();

                $table->index('division_id');
            });
        }

        if (!Schema::hasTable('upazilas')) {
            Schema::create('upazilas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('district_id');
                $table->string('name');
                $table->string('bn_name');
                $table->string('lat')->nullable();
                $table->string('lng')->nullable();
                $table->timestamps();

                $table->index('district_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upazilas');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('divisions');
    }
};
