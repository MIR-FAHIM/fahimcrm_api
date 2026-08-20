<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('attendance_method_id')
                ->nullable()
                ->after('designation_id')
                ->constrained('attendance_methods')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['attendance_method_id']);
            $table->dropColumn('attendance_method_id');
        });
    }
};
