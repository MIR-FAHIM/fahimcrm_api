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
        Schema::table('feature_lists', function (Blueprint $table) {
            $table->string('feature_key')->nullable()->unique()->after('feature_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feature_lists', function (Blueprint $table) {
            $table->dropUnique(['feature_key']);
            $table->dropColumn('feature_key');
        });
    }
};