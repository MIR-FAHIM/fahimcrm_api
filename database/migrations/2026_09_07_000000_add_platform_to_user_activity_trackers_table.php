<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_activity_trackers') && !Schema::hasColumn('user_activity_trackers', 'platform')) {
            Schema::table('user_activity_trackers', function (Blueprint $table) {
                $table->string('platform', 20)->nullable()->after('type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_activity_trackers') && Schema::hasColumn('user_activity_trackers', 'platform')) {
            Schema::table('user_activity_trackers', function (Blueprint $table) {
                $table->dropColumn('platform');
            });
        }
    }
};
