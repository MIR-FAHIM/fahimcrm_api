<?php

namespace Tests\Feature;

use App\Models\Prospect;
use App\Models\ProspectConcernPersonTeam;
use App\Models\Role;
use App\Models\User;
use App\Models\UserActivityTracker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteEmployeeTest extends TestCase
{
    use DatabaseMigrations;

    protected function migrateFreshUsing(): array
    {
        return ['--path' => [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2025_03_11_052346_create_roles_table.php',
            'database/migrations/2025_03_17_141440_create_task_assigned_persons_table.php',
            'database/migrations/2025_04_10_123849_create_prospects_table.php',
            'database/migrations/2025_05_12_120935_create_prospect_concern_person_teams_table.php',
            'database/migrations/2025_07_10_122318_create_user_activity_trackers_table.php',
            'database/migrations/2026_09_07_000000_add_platform_to_user_activity_trackers_table.php',
            'database/migrations/2026_09_13_000000_create_api_error_logs_table.php',
            'database/migrations/2026_10_14_000000_add_lookup_indexes_to_prospect_concern_person_teams_table.php',
        ]];
    }

    private function createUser(array $attributes = [], string $roleName = 'Employee'): User
    {
        $role = Role::firstOrCreate(['role_name' => $roleName], ['isActive' => true]);

        return User::forceCreate(array_merge([
            'name' => 'Test User',
            'email' => uniqid('user').'@example.com',
            'password' => 'Password123!',
            'isActive' => true,
            'role_id' => $role->id,
            'app_token' => uniqid('token_'),
        ], $attributes));
    }

    public function test_delete_employee_requires_token(): void
    {
        $target = $this->createUser();
        $this->deleteJson('/api/delete-employee/' . $target->id)->assertStatus(450);
    }

    public function test_delete_employee_denied_for_regular_employee_and_admin(): void
    {
        $target = $this->createUser();
        $employee = $this->createUser([], 'Employee');
        $admin = $this->createUser([], 'Admin');

        $this->deleteJson('/api/delete-employee/' . $target->id, [], ['token' => $employee->app_token])
            ->assertStatus(403)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Only active Super Admin users can delete an employee.');

        $this->deleteJson('/api/delete-employee/' . $target->id, [], ['token' => $admin->app_token])
            ->assertStatus(403)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Only active Super Admin users can delete an employee.');

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_delete_employee_denied_for_inactive_super_admin(): void
    {
        $target = $this->createUser();
        $inactiveSuperAdmin = $this->createUser(['isActive' => false], 'Super Admin');

        $this->deleteJson('/api/delete-employee/' . $target->id, [], ['token' => $inactiveSuperAdmin->app_token])
            ->assertStatus(403)
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_super_admin_cannot_delete_their_own_account(): void
    {
        $superAdmin = $this->createUser([], 'Super Admin');

        $this->deleteJson('/api/delete-employee/' . $superAdmin->id, [], ['token' => $superAdmin->app_token])
            ->assertStatus(400)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'You cannot delete your own account.');

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }

    public function test_delete_employee_returns_404_if_employee_does_not_exist(): void
    {
        $superAdmin = $this->createUser([], 'Super Admin');

        $this->deleteJson('/api/delete-employee/999999', [], ['token' => $superAdmin->app_token])
            ->assertStatus(404)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Employee not found.');
    }

    public function test_super_admin_can_delete_employee_and_cleans_up_prospect_team(): void
    {
        $superAdmin = $this->createUser([], 'Super Admin');
        $target = $this->createUser(['name' => 'Target Employee'], 'Employee');

        $prospect = Prospect::create([
            'prospect_name' => 'Test Prospect',
            'type' => 'prospect',
            'is_active' => 1,
        ]);

        ProspectConcernPersonTeam::create([
            'prospect_id' => $prospect->id,
            'employee_id' => $target->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', ['id' => $target->id]);
        $this->assertDatabaseHas('prospect_concern_person_teams', [
            'prospect_id' => $prospect->id,
            'employee_id' => $target->id,
        ]);

        $response = $this->deleteJson('/api/delete-employee/' . $target->id, [], [
            'token' => $superAdmin->app_token,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Employee deleted successfully');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('prospect_concern_person_teams', [
            'prospect_id' => $prospect->id,
            'employee_id' => $target->id,
        ]);

        $this->assertDatabaseHas('user_activity_trackers', [
            'user_id' => $superAdmin->id,
            'activity_name' => 'Employee deleted',
        ]);
    }

    public function test_post_method_also_works_for_delete_employee(): void
    {
        $superAdmin = $this->createUser([], 'Super Admin');
        $target = $this->createUser([], 'Employee');

        $response = $this->postJson('/api/delete-employee/' . $target->id, [], [
            'token' => $superAdmin->app_token,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }
}
