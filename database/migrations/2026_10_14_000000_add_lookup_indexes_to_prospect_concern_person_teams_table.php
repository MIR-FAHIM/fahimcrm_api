<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospect_concern_person_teams', function (Blueprint $table) {
            $table->index(['prospect_id', 'employee_id'], 'prospect_team_prospect_employee_index');
            $table->index(['employee_id', 'is_active'], 'prospect_team_employee_active_index');
        });
    }

    public function down(): void
    {
        Schema::table('prospect_concern_person_teams', function (Blueprint $table) {
            $table->dropIndex('prospect_team_prospect_employee_index');
            $table->dropIndex('prospect_team_employee_active_index');
        });
    }
};
