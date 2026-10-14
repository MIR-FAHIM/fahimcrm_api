<?php

namespace Tests\Feature;

use App\Models\AddProspectContact;
use App\Models\Prospect;
use App\Models\ProspectConcernPersonTeam;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ProspectTeamAdminTest extends TestCase
{
    use DatabaseMigrations;

    protected function migrateFreshUsing(): array
    {
        // Restrict the suite to module dependencies; older ticket migrations require MySQL.
        return ['--path' => [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2025_03_11_052346_create_roles_table.php',
            'database/migrations/2025_04_10_112403_create_prospect_stages_table.php',
            'database/migrations/2025_04_10_123849_create_prospects_table.php',
            'database/migrations/2025_05_12_120935_create_prospect_concern_person_teams_table.php',
            'database/migrations/2025_07_10_122318_create_user_activity_trackers_table.php',
            'database/migrations/2026_09_13_000000_create_api_error_logs_table.php',
            'database/migrations/2026_10_14_000000_add_lookup_indexes_to_prospect_concern_person_teams_table.php',
        ]];
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

    private function adminHeaders(string $roleName = 'Admin'): array
    {
        $role = Role::create(['role_name' => $roleName, 'isActive' => true]);
        $user = $this->employee(['role_id' => $role->id]);

        return ['token' => $user->app_token];
    }

    private function prospect(array $attributes = []): Prospect
    {
        return Prospect::create(array_merge([
            'prospect_name' => 'Example Prospect',
            'type' => 'prospect',
            'is_active' => 1,
        ], $attributes));
    }

    public function test_admin_endpoints_require_a_token_and_an_admin_role(): void
    {
        $this->getJson('/api/prospect-concern-person/admin/prospects')->assertStatus(450);
        $employee = $this->employee();
        $headers = ['token' => $employee->app_token];
        $this->getJson('/api/prospect-concern-person/admin/prospects', $headers)->assertForbidden();
        foreach (['add', 'remove', 'bulk-add'] as $action) {
            $this->postJson('/api/prospect-concern-person/admin/'.$action, [], $headers)
                ->assertForbidden()->assertJsonPath('status', 'failed');
        }
    }

    public function test_inactive_admin_is_denied_and_super_admin_is_allowed(): void
    {
        $headers = $this->adminHeaders('Super Admin');
        $this->getJson('/api/prospect-concern-person/admin/prospects', $headers)->assertOk();
        User::where('app_token', $headers['token'])->update(['isActive' => false]);
        $this->getJson('/api/prospect-concern-person/admin/prospects', $headers)->assertForbidden();
    }

    public function test_admin_list_returns_employee_teams_without_changing_contact_person_relationship(): void
    {
        $headers = $this->adminHeaders();
        $employee = $this->employee();
        $prospect = $this->prospect();
        ProspectConcernPersonTeam::create([
            'prospect_id' => $prospect->id, 'employee_id' => $employee->id,
            'is_active' => true, 'notify' => false,
        ]);
        $this->prospect(['type' => 'client']);
        $this->prospect(['is_active' => 0]);

        $response = $this->getJson('/api/prospect-concern-person/admin/prospects', $headers);
        $response->assertOk()->assertJsonPath('status', 'success')
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.team_members_count', 1)
            ->assertJsonPath('data.0.active_team_members_count', 1)
            ->assertJsonPath('data.0.team_members.0.employee.id', $employee->id)
            ->assertJsonPath('data.0.team_members.0.is_active', true)
            ->assertJsonPath('data.0.team_members.0.notify', false);
        $this->assertArrayNotHasKey('app_token', $response->json('data.0.team_members.0.employee'));
        $this->assertInstanceOf(AddProspectContact::class, $prospect->concernPersons()->getRelated());
    }

    public function test_list_filters_active_assignments_and_paginates(): void
    {
        $headers = $this->adminHeaders();
        $employee = $this->employee();
        $assigned = $this->prospect(['prospect_name' => 'Alpha']);
        $inactiveAssignment = $this->prospect(['prospect_name' => 'Beta']);
        $this->prospect(['prospect_name' => 'Gamma']);
        foreach ([$assigned, $inactiveAssignment] as $index => $prospect) {
            ProspectConcernPersonTeam::create([
                'prospect_id' => $prospect->id, 'employee_id' => $employee->id,
                'is_active' => $index === 0,
            ]);
        }
        $this->getJson('/api/prospect-concern-person/admin/prospects?employee_id='.$employee->id, $headers)
            ->assertOk()->assertJsonPath('pagination.total', 1)->assertJsonPath('data.0.id', $assigned->id);
        $this->getJson('/api/prospect-concern-person/admin/prospects?team_status=assigned', $headers)
            ->assertOk()->assertJsonPath('pagination.total', 1);
        $this->getJson('/api/prospect-concern-person/admin/prospects?team_status=unassigned', $headers)
            ->assertOk()->assertJsonPath('pagination.total', 2);
        $this->getJson('/api/prospect-concern-person/admin/prospects?search=Beta', $headers)
            ->assertOk()->assertJsonPath('data.0.id', $inactiveAssignment->id);
        $this->getJson('/api/prospect-concern-person/admin/prospects?per_page=1&page=2', $headers)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('pagination.current_page', 2)
            ->assertJsonPath('pagination.total', 3)->assertJsonPath('pagination.last_page', 3);
    }

    public function test_admin_can_add_multiple_members_and_repeated_add_preserves_notify(): void
    {
        $headers = $this->adminHeaders();
        $first = $this->employee();
        $second = $this->employee();
        $prospect = $this->prospect();
        $this->postJson('/api/prospect-concern-person/admin/add', [
            'prospect_id' => $prospect->id,
            'employees' => [
                ['employee_id' => $first->id, 'notify' => true],
                ['employee_id' => $second->id, 'notify' => false],
            ],
        ], $headers)->assertOk()->assertJsonPath('status', 'success')->assertJsonCount(2, 'data');
        $this->postJson('/api/prospect-concern-person/admin/add', [
            'prospect_id' => $prospect->id,
            'employees' => [['employee_id' => $first->id]],
        ], $headers)->assertOk()->assertJsonPath('data.0.notify', true);
        $this->assertDatabaseCount('prospect_concern_person_teams', 2);
    }

    public function test_bulk_add_preserves_other_members_reactivates_and_is_repeatable(): void
    {
        $headers = $this->adminHeaders();
        $employee = $this->employee();
        $other = $this->employee();
        $first = $this->prospect();
        $second = $this->prospect();
        $third = $this->prospect();
        ProspectConcernPersonTeam::create([
            'prospect_id' => $first->id, 'employee_id' => $employee->id, 'is_active' => true, 'notify' => true,
        ]);
        ProspectConcernPersonTeam::create([
            'prospect_id' => $second->id, 'employee_id' => $employee->id, 'is_active' => false, 'notify' => true,
        ]);
        ProspectConcernPersonTeam::create([
            'prospect_id' => $third->id, 'employee_id' => $other->id, 'is_active' => true,
        ]);
        $payload = ['employee_id' => $employee->id, 'prospect_ids' => [$first->id, $second->id, $third->id], 'notify' => false];
        $this->postJson('/api/prospect-concern-person/admin/bulk-add', $payload, $headers)->assertOk()
            ->assertJsonPath('data', ['total' => 3, 'added' => 1, 'reactivated' => 1, 'skipped' => 1]);
        $this->assertDatabaseHas('prospect_concern_person_teams', [
            'prospect_id' => $second->id, 'employee_id' => $employee->id, 'is_active' => true, 'notify' => true,
        ]);
        $this->assertDatabaseHas('prospect_concern_person_teams', [
            'prospect_id' => $third->id, 'employee_id' => $other->id,
        ]);
        $this->postJson('/api/prospect-concern-person/admin/bulk-add', $payload, $headers)->assertOk()
            ->assertJsonPath('data', ['total' => 3, 'added' => 0, 'reactivated' => 0, 'skipped' => 3]);
        $this->assertDatabaseCount('prospect_concern_person_teams', 4);
    }

    public function test_invalid_bulk_selection_does_not_partially_assign(): void
    {
        $headers = $this->adminHeaders();
        $employee = $this->employee();
        $prospect = $this->prospect();
        $this->postJson('/api/prospect-concern-person/admin/bulk-add', [
            'employee_id' => $employee->id, 'prospect_ids' => [$prospect->id, 999999],
        ], $headers)->assertUnprocessable()->assertJsonValidationErrors('prospect_ids.1');
        $this->assertDatabaseCount('prospect_concern_person_teams', 0);
    }

    public function test_bulk_request_is_limited_to_500_prospects(): void
    {
        $headers = $this->adminHeaders();
        $employee = $this->employee();
        $this->postJson('/api/prospect-concern-person/admin/bulk-add', [
            'employee_id' => $employee->id, 'prospect_ids' => range(1, 501),
        ], $headers)->assertUnprocessable()->assertJsonValidationErrors('prospect_ids');
        $this->assertDatabaseCount('prospect_concern_person_teams', 0);
    }

    public function test_bulk_rejects_duplicate_ids_inactive_employees_and_non_prospects(): void
    {
        $headers = $this->adminHeaders();
        $employee = $this->employee();
        $prospect = $this->prospect();
        $this->postJson('/api/prospect-concern-person/admin/bulk-add', [
            'employee_id' => $employee->id, 'prospect_ids' => [$prospect->id, $prospect->id],
        ], $headers)->assertUnprocessable();
        $client = $this->prospect(['type' => 'client']);
        $this->postJson('/api/prospect-concern-person/admin/bulk-add', [
            'employee_id' => $employee->id, 'prospect_ids' => [$client->id],
        ], $headers)->assertUnprocessable();
        $employee->update(['isActive' => false]);
        $this->postJson('/api/prospect-concern-person/admin/bulk-add', [
            'employee_id' => $employee->id, 'prospect_ids' => [$prospect->id],
        ], $headers)->assertUnprocessable();
        $this->assertDatabaseCount('prospect_concern_person_teams', 0);
    }

    public function test_remove_only_deletes_the_selected_employee_assignment(): void
    {
        $headers = $this->adminHeaders();
        $first = $this->employee();
        $second = $this->employee();
        $prospect = $this->prospect();
        foreach ([$first, $second] as $employee) {
            ProspectConcernPersonTeam::create(['prospect_id' => $prospect->id, 'employee_id' => $employee->id]);
        }
        $payload = ['prospect_id' => $prospect->id, 'employee_id' => $first->id];
        $this->postJson('/api/prospect-concern-person/admin/remove', $payload, $headers)
            ->assertOk()->assertJsonPath('status', 'success');
        $this->assertDatabaseCount('prospect_concern_person_teams', 1);
        $this->assertDatabaseHas('prospect_concern_person_teams', ['employee_id' => $second->id]);
        $this->postJson('/api/prospect-concern-person/admin/remove', $payload, $headers)->assertNotFound();
    }

    public function test_existing_single_prospect_endpoints_keep_their_payloads(): void
    {
        $employee = $this->employee();
        $headers = ['token' => $employee->app_token];
        $prospect = $this->prospect();
        $this->postJson('/api/prospect-concern-person/add', [
            'prospect_id' => $prospect->id, 'employees' => [['employee_id' => $employee->id]],
        ], $headers)->assertOk()->assertJsonPath('status', 'success');
        $this->getJson('/api/prospect-concern-person/'.$prospect->id, $headers)
            ->assertOk()->assertJsonPath('data.0.employee_id', $employee->id);
        $this->postJson('/api/prospect-concern-person/remove', [
            'prospect_id' => $prospect->id, 'employee_id' => $employee->id,
        ], $headers)->assertOk()->assertJsonPath('status', 'success');
        $this->postJson('/api/prospect-concern-person/add', [
            'prospect_id' => $prospect->id, 'employees' => [['employee_id' => 999999]],
        ], $headers)->assertUnprocessable();
    }

    public function test_admin_can_remove_a_deleted_employee_assignment(): void
    {
        $headers = $this->adminHeaders();
        $prospect = $this->prospect();
        ProspectConcernPersonTeam::create(['prospect_id' => $prospect->id, 'employee_id' => 999999]);
        $response = $this->getJson('/api/prospect-concern-person/admin/prospects', $headers);
        $response->assertOk()->assertJsonPath('data.0.team_members.0.employee', null);
        $this->postJson('/api/prospect-concern-person/admin/remove', [
            'prospect_id' => $prospect->id, 'employee_id' => 999999,
        ], $headers)->assertOk();
        $this->assertDatabaseCount('prospect_concern_person_teams', 0);
    }
}
