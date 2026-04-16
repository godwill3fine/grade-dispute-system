<?php

namespace Tests\Feature;

use App\Models\Dispute;
use App\Models\Notification;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use PDO;
use Tests\TestCase;

class AdminUserDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite is not enabled; install/enable it to run in-memory database tests.');
        }

        Artisan::call('migrate:fresh');
    }

    public function test_admin_can_delete_an_unused_account_from_manage_users(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'identifier' => 'ADM100',
            'role' => 'admin',
            'status' => 'active',
            'password' => 'password',
        ]);

        $target = User::create([
            'name' => 'Target User',
            'email' => 'target@example.test',
            'identifier' => 'USR100',
            'role' => 'admin',
            'status' => 'active',
            'password' => 'password',
        ]);

        Staff::create([
            'staff_id' => 'USR100',
            'first_name' => 'Target',
            'last_name' => 'User',
            'role' => 'admin',
            'department' => 'Administration',
        ]);

        Notification::create([
            'recipient_id' => 'USR100',
            'message' => 'Welcome aboard',
        ]);

        $this->actingAs($admin)
            ->postJson('/admin/users/USR100/actions', [
                'action' => 'delete',
            ])
            ->assertOk()
            ->assertJsonPath('removed', true);

        $this->assertDatabaseMissing('users', [
            'identifier' => 'USR100',
        ]);

        $this->assertDatabaseMissing('staff', [
            'staff_id' => 'USR100',
        ]);

        $this->assertDatabaseMissing('notifications', [
            'recipient_id' => 'USR100',
        ]);

        $this->assertDatabaseHas('users', [
            'identifier' => 'ADM100',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Delete User',
        ]);
    }

    public function test_admin_cannot_delete_an_account_with_dispute_history(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'identifier' => 'ADM100',
            'role' => 'admin',
            'status' => 'active',
            'password' => 'password',
        ]);

        User::create([
            'name' => 'Student User',
            'email' => 'student@example.test',
            'identifier' => 'STD100',
            'role' => 'student',
            'status' => 'active',
            'password' => 'password',
        ]);

        Student::create([
            'reg_number' => 'STD100',
            'first_name' => 'Student',
            'last_name' => 'User',
            'course' => 'BBC',
            'year' => 2,
            'department' => 'Computer Science',
            'faculty' => 'COPAS',
        ]);

        Dispute::create([
            'student_id' => 'STD100',
            'type' => 'general',
            'assigned_to' => 'ADM100',
            'assigned_role' => 'admin',
            'title' => 'Exam Mark Review',
            'description' => 'Please review this result.',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->postJson('/admin/users/STD100/actions', [
                'action' => 'delete',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['action']);

        $this->assertDatabaseHas('users', [
            'identifier' => 'STD100',
        ]);

        $this->assertDatabaseHas('students', [
            'reg_number' => 'STD100',
        ]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'identifier' => 'ADM100',
            'role' => 'admin',
            'status' => 'active',
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->postJson('/admin/users/ADM100/actions', [
                'action' => 'delete',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['action']);

        $this->assertDatabaseHas('users', [
            'identifier' => 'ADM100',
        ]);
    }
}
