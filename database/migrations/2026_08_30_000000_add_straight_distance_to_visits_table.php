<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('visits') && !Schema::hasColumn('visits', 'straight_distance_meters')) {
            $hasCompleteLocation = Schema::hasColumn('visits', 'complete_location');

            Schema::table('visits', function (Blueprint $table) use ($hasCompleteLocation) {
                $column = $table->decimal('straight_distance_meters', 10, 2)->nullable();

                if ($hasCompleteLocation) {
                    $column->after('complete_location');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('visits') && Schema::hasColumn('visits', 'straight_distance_meters')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->dropColumn('straight_distance_meters');
            });
        }
    }
};
