<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'is_prospect_table_view')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_prospect_table_view')->default(true)->after('is_dark_mode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_prospect_table_view')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_prospect_table_view');
            });
        }
    }
};
