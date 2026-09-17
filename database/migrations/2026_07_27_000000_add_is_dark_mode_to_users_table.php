<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'is_dark_mode')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_dark_mode')
                    ->default(false)
                    ->after('end_min');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_dark_mode')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_dark_mode');
            });
        }
    }
};
