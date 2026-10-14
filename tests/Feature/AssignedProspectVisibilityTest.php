<?php

namespace Tests\Feature;

use App\Models\Prospect;
use App\Models\ProspectConcernPersonTeam;
use App\Models\ProspectLogActivity;
use App\Models\ProspectStage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AssignedProspectVisibilityTest extends TestCase
{
    use DatabaseMigrations;

    protected function migrateFreshUsing(): array
    {
        // Migrate only this module's dependencies; older ticket migrations require MySQL.
        return ['--path' => [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2025_03_11_052346_create_roles_table.php',
            'database/migrations/2025_03_17_140954_create_priorities_table.php',
            'database/migrations/2025_04_10_112403_create_prospect_stages_table.php',
            'database/migrations/2025_04_10_120339_create_industry_types_table.php',
            'database/migrations/2025_04_10_120504_create_information_sources_table.php',
            'database/migrations/2025_04_10_123849_create_prospects_table.php',
            'database/migrations/2025_04_19_165019_create_prospect_log_activities_table.php',
            'database/migrations/2025_04_20_122059_create_add_prospect_contacts_table.php',
            'database/migrations/2025_05_11_124830_create_zones_table.php',
            'database/migrations/2025_05_12_120935_create_prospect_concern_person_teams_table.php',
            'database/migrations/2025_05_17_180015_create_product_items_table.php',
            'database/migrations/2025_07_10_122318_create_user_activity_trackers_table.php',
            'database/migrations/2026_08_09_000000_create_divisions_districts_upazilas_tables.php',
            'database/migrations/2026_09_13_000000_create_api_error_logs_table.php',
            'database/migrations/2026_10_14_000000_add_lookup_indexes_to_prospect_concern_person_teams_table.php',
        ]];
    }

    protected function afterRefreshingDatabase(): void
    {
        // These location columns were added manually to the deployed database.
        Schema::table('prospects', function (Blueprint $table) {
            $table->unsignedBigInteger('division_id')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->unsignedBigInteger('thana_id')->nullable();
        });
    }

    private function employee(array $attributes = []): User
    {
        return User::forceCreate(array_merge([
            'name' => 'Employee',
            'email' => uniqid('employee').'@example.com',
            'password' => 'TestPassword123',
            'isActive' => true,
            'app_token' => uniqid('token'),
        ], $attributes));
    }

    private function prospect(array $attributes = []): Prospect
    {
        return Prospect::create(array_merge([
            'prospect_name' => 'Example Prospect',
            'type' => 'prospect',
            'is_active' => 1,
        ], $attributes));
    }

    private function assign(Prospect $prospect, User $employee, bool $active = true): void
    {
        ProspectConcernPersonTeam::create([
            'prospect_id' => $prospect->id,
            'employee_id' => $employee->id,
            'is_active' => $active,
            'notify' => false,
        ]);
    }

    public function test_list_uses_the_token_employee_and_returns_only_active_assigned_prospects(): void
    {
        $employee = $this->employee();
        $other = $this->employee();
        $visible = $this->prospect();
        $hidden = $this->prospect();
        $inactiveAssignment = $this->prospect();
        $inactiveProspect = $this->prospect(['is_active' => 0]);
        $warehouse = $this->prospect(['type' => 'warehouse']);
        $this->prospect();
        $this->assign($visible, $employee);
        $this->assign($visible, $other);
        $this->assign($hidden, $other);
        $this->assign($inactiveAssignment, $employee, false);
        $this->assign($inactiveProspect, $employee);
        $this->assign($warehouse, $employee);
        ProspectLogActivity::create(['prospect_id' => $visible->id, 'activity_type' => 'call']);
        ProspectLogActivity::create(['prospect_id' => $hidden->id, 'activity_type' => 'call']);

        $this->getJson('/api/get-all-prospect?user_id='.$other->id.'&employee_id='.$other->id, [
            'token' => $employee->app_token,
        ])->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id)->assertJsonPath('data.0.activity_summary.call', 1);
    }

    public function test_employee_with_no_assignments_receives_an_empty_list_and_zero_stage_counts(): void
    {
        $employee = $this->employee();
        $other = $this->employee();
        $stage = ProspectStage::create(['stage_name' => 'New']);
        $this->assign($this->prospect(['stage_id' => $stage->id]), $other);

        $headers = ['token' => $employee->app_token];
        $this->getJson('/api/get-all-prospect', $headers)->assertOk()->assertJsonPath('data', []);
        $this->getJson('/api/get-prospect-stage-overview', $headers)->assertOk()
            ->assertJsonPath('data.0.prospects_count', 0);
    }

    public function test_stage_overview_counts_only_the_current_employees_visible_prospects(): void
    {
        $employee = $this->employee();
        $other = $this->employee();
        $new = ProspectStage::create(['stage_name' => 'New']);
        $qualified = ProspectStage::create(['stage_name' => 'Qualified']);
        $empty = ProspectStage::create(['stage_name' => 'Empty']);
        $visible = $this->prospect(['stage_id' => $new->id]);
        $this->assign($visible, $employee);
        // Legacy duplicate links must not count the same prospect twice.
        $this->assign($visible, $employee);
        $this->assign($visible, $other);
        $this->assign($this->prospect(['stage_id' => $new->id]), $other);
        $this->assign($this->prospect(['stage_id' => $new->id]), $employee, false);
        $this->assign($this->prospect(['stage_id' => $new->id, 'is_active' => 0]), $employee);
        $this->assign($this->prospect(['stage_id' => $new->id, 'type' => 'warehouse']), $employee);
        $this->assign($this->prospect(['stage_id' => $qualified->id]), $employee);

        $response = $this->getJson('/api/get-prospect-stage-overview?user_id='.$other->id, [
            'token' => $employee->app_token,
        ])->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(3, 'data');
        $counts = collect($response->json('data'))->keyBy('id');
        $this->assertSame(1, $counts[$new->id]['prospects_count']);
        $this->assertSame(1, $counts[$qualified->id]['prospects_count']);
        $this->assertSame(0, $counts[$empty->id]['prospects_count']);
        $this->getJson('/api/get-all-prospect', ['token' => $employee->app_token])
            ->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_search_and_stage_filters_cannot_bypass_assignment_visibility(): void
    {
        $employee = $this->employee();
        $other = $this->employee();
        $stage = ProspectStage::create(['stage_name' => 'New']);
        $visible = $this->prospect(['prospect_name' => 'Shared Name', 'stage_id' => $stage->id]);
        $hidden = $this->prospect(['prospect_name' => 'Shared Name', 'stage_id' => $stage->id]);
        $this->assign($visible, $employee);
        $this->assign($hidden, $other);
        $headers = ['token' => $employee->app_token];

        $this->getJson('/api/get-all-prospect?search=Shared&stage_id='.$stage->id, $headers)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $visible->id);
        $this->getJson('/api/get-all-prospect?prospect_name=Shared&stage_id='.$stage->id, $headers)
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_admin_and_super_admin_also_require_assignment_on_these_two_endpoints(): void
    {
        $other = $this->employee();
        $stage = ProspectStage::create(['stage_name' => 'New']);
        $this->assign($this->prospect(['stage_id' => $stage->id]), $other);
        foreach (['Admin', 'Super Admin'] as $roleName) {
            $role = Role::create(['role_name' => $roleName, 'isActive' => true]);
            $admin = $this->employee(['role_id' => $role->id]);
            $own = $this->prospect(['stage_id' => $stage->id]);
            $this->assign($own, $admin);
            $headers = ['token' => $admin->app_token];
            $this->getJson('/api/get-all-prospect', $headers)->assertOk()
                ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id);
            $this->getJson('/api/get-prospect-stage-overview', $headers)->assertOk()
                ->assertJsonPath('data.0.prospects_count', 1);
            $this->getJson('/api/prospect-concern-person/admin/prospects', $headers)->assertOk()
                ->assertJsonPath('pagination.total', $roleName === 'Admin' ? 2 : 3);
        }
    }

    public function test_deactivating_or_removing_an_assignment_immediately_hides_the_prospect(): void
    {
        $employee = $this->employee();
        $stage = ProspectStage::create(['stage_name' => 'New']);
        $prospect = $this->prospect(['stage_id' => $stage->id]);
        $this->assign($prospect, $employee);
        $headers = ['token' => $employee->app_token];
        $this->getJson('/api/get-all-prospect', $headers)->assertOk()->assertJsonCount(1, 'data');
        ProspectConcernPersonTeam::where('employee_id', $employee->id)->update(['is_active' => false]);
        $this->getJson('/api/get-all-prospect', $headers)->assertOk()->assertJsonPath('data', []);
        $this->getJson('/api/get-prospect-stage-overview', $headers)->assertOk()
            ->assertJsonPath('data.0.prospects_count', 0);
        ProspectConcernPersonTeam::where('employee_id', $employee->id)->delete();
        $this->getJson('/api/get-all-prospect', $headers)->assertOk()->assertJsonPath('data', []);
    }

    public function test_both_endpoints_reject_missing_or_invalid_tokens(): void
    {
        foreach (['/api/get-all-prospect', '/api/get-prospect-stage-overview'] as $endpoint) {
            $this->getJson($endpoint)->assertStatus(450);
            $this->getJson($endpoint, ['token' => 'invalid-token'])->assertStatus(450);
        }
    }
}
