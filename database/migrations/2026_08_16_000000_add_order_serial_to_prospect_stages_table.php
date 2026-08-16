<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('prospect_stages') && !Schema::hasColumn('prospect_stages', 'order_serial')) {
            Schema::table('prospect_stages', function (Blueprint $table) {
                $table->unsignedInteger('order_serial')->nullable()->after('color_code')->index();
            });

            DB::table('prospect_stages')->orderBy('id')->get()->each(function ($stage) {
                DB::table('prospect_stages')
                    ->where('id', $stage->id)
                    ->update(['order_serial' => $stage->id]);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('prospect_stages') && Schema::hasColumn('prospect_stages', 'order_serial')) {
            Schema::table('prospect_stages', function (Blueprint $table) {
                $table->dropColumn('order_serial');
            });
        }
    }
};
