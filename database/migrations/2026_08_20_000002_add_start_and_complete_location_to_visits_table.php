<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('visits')) {
            return;
        }

        Schema::table('visits', function (Blueprint $table) {
            if (!Schema::hasColumn('visits', 'start_latitude')) {
                $table->decimal('start_latitude', 10, 8)->nullable()->after('checkin_longitude');
            }

            if (!Schema::hasColumn('visits', 'start_longitude')) {
                $table->decimal('start_longitude', 11, 8)->nullable()->after('start_latitude');
            }

            if (!Schema::hasColumn('visits', 'start_location')) {
                $table->string('start_location')->nullable()->after('start_longitude');
            }

            if (!Schema::hasColumn('visits', 'complete_latitude')) {
                $table->decimal('complete_latitude', 10, 8)->nullable()->after('start_location');
            }

            if (!Schema::hasColumn('visits', 'complete_longitude')) {
                $table->decimal('complete_longitude', 11, 8)->nullable()->after('complete_latitude');
            }

            if (!Schema::hasColumn('visits', 'complete_location')) {
                $table->string('complete_location')->nullable()->after('complete_longitude');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('visits')) {
            return;
        }

        Schema::table('visits', function (Blueprint $table) {
            $columns = [
                'start_latitude',
                'start_longitude',
                'start_location',
                'complete_latitude',
                'complete_longitude',
                'complete_location',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('visits', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
