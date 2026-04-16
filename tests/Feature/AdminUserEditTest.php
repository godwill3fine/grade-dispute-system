<?php

namespace Tests\Feature;

use App\Mail\AdminUserWelcomeMail;
use App\Models\Lecturer;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use PDO;
use Tests\TestCase;

class AdminUserEditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite is not enabled; install/enable it to run in-memory database tests.');
        }

        Artisan::call('migrate:fresh');
    }

    public function test_admin_can_update_a_user_and_change_their_role(): void
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
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.test',
            'identifier' => 'LEC100',
            'role' => 'lecturer',
            'status' => 'active',
            'password' => 'password',
        ]);

        Lecturer::create([
            'lecturer_id' => 'LEC100',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'department' => 'History',
        ]);

        $response = $this
            ->actingAs($admin)
            ->postJson('/admin/users/LEC100', [
                'first_name' => 'Case',
                'last_name' => 'Owner',
                'email' => 'case.owner@example.test',
                'role' => 'admin',
                'department' => 'Administration',
                'salutation' => 'Mr.',
                'status' => 'active',
                'office_location' => 'Senate Building',
                'require_password_reset' => false,
                'manual_approval_required' => false,
                'send_account_email' => false,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.identifier', 'LEC100')
            ->assertJsonPath('user.role', 'admin')
            ->assertJsonPath('user.status', 'active');

        $this->assertDatabaseHas('users', [
            'identifier' => 'LEC100',
            'name' => 'Case Owner',
            'email' => 'case.owner@example.test',
            'role' => 'admin',
            'status' => 'active',
            'office_location' => 'Senate Building',
        ]);

        $this->assertDatabaseMissing('lecturers', [
            'lecturer_id' => 'LEC100',
        ]);

        $this->assertDatabaseHas('staff', [
            'staff_id' => 'LEC100',
            'first_name' => 'Case',
            'last_name' => 'Owner',
            'role' => 'admin',
            'department' => 'Administration',
        ]);
    }

    public function test_admin_can_send_password_reset_instructions_from_the_edit_page(): void
    {
        Mail::fake();

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

        $response = $this
            ->actingAs($admin)
            ->postJson('/admin/users/USR100/actions', [
                'action' => 'send_password_reset',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('reload', true);

        $target->refresh();

        $this->assertTrue($target->require_password_reset);
        $this->assertNotNull($target->welcome_email_sent_at);

        Mail::assertSent(AdminUserWelcomeMail::class, function (AdminUserWelcomeMail $mail): bool {
            return $mail->hasTo('target@example.test')
                && $mail->identifier === 'USR100'
                && $mail->requirePasswordReset;
        });
    }

    public function test_admin_cannot_deactivate_their_own_account_from_the_edit_page(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'identifier' => 'ADM100',
            'role' => 'admin',
            'status' => 'active',
            'password' => 'password',
        ]);

        Staff::create([
            'staff_id' => 'ADM100',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role' => 'admin',
            'department' => 'Administration',
        ]);

        $this->actingAs($admin)
            ->postJson('/admin/users/ADM100/actions', [
                'action' => 'deactivate',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['action']);

        $admin->refresh();

        $this->assertSame('active', $admin->status);
    }
}
