<?php

namespace Tests\Feature;

use App\Mail\AdminUserWelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PDO;
use Tests\TestCase;

class AdminUserCreateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite is not enabled; install/enable it to run in-memory database tests.');
        }

        Artisan::call('migrate:fresh');
    }

    public function test_admin_can_create_student_user_with_role_specific_fields_and_security_flags(): void
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

        $response = $this
            ->actingAs($admin)
            ->postJson('/admin/users', [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@example.test',
                'identifier' => 'STD100',
                'role' => 'student',
                'department' => 'Computer Science',
                'faculty' => 'COPAS',
                'course' => 'BBC',
                'year' => 2,
                'status' => 'active',
                'office_location' => 'Main Campus - Block B',
                'send_welcome_email' => true,
                'require_password_reset' => true,
                'manual_approval_required' => true,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.identifier', 'STD100')
            ->assertJsonPath('user.status', 'pending invite')
            ->assertJsonPath('welcome_email_sent', true)
            ->assertJsonPath('require_password_reset', true)
            ->assertJsonPath('manual_approval_required', true);

        $this->assertDatabaseHas('users', [
            'identifier' => 'STD100',
            'role' => 'student',
            'status' => 'pending invite',
            'office_location' => 'Main Campus - Block B',
            'require_password_reset' => 1,
            'manual_approval_required' => 1,
        ]);

        $this->assertDatabaseHas('students', [
            'reg_number' => 'STD100',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'course' => 'BBC',
            'year' => 2,
            'department' => 'Computer Science',
            'faculty' => 'COPAS',
        ]);

        $createdUser = User::query()->where('identifier', 'STD100')->first();

        $this->assertNotNull($createdUser);
        $this->assertNotNull($createdUser->welcome_email_sent_at);
        $this->assertTrue(Hash::check('STD100', $createdUser->password));

        Mail::assertSent(AdminUserWelcomeMail::class, function (AdminUserWelcomeMail $mail): bool {
            return $mail->hasTo('john.smith@example.test')
                && $mail->identifier === 'STD100'
                && $mail->requirePasswordReset
                && $mail->manualApprovalRequired;
        });

        $this->postJson('/login', [
            'identifier' => 'STD100',
            'password' => 'STD100',
        ])->assertStatus(403)
            ->assertJsonPath('error', 'This account is waiting for administrative approval before sign-in is enabled.');
    }

    public function test_lecturer_creation_requires_a_department(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'identifier' => 'ADM100',
            'role' => 'admin',
            'status' => 'active',
            'password' => 'password',
        ]);

        $response = $this
            ->actingAs($admin)
            ->postJson('/admin/users', [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane.doe@example.test',
                'identifier' => 'LEC100',
                'role' => 'lecturer',
                'status' => 'active',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['department']);

        $this->assertDatabaseMissing('users', [
            'identifier' => 'LEC100',
        ]);
    }

    public function test_admin_can_create_staff_user_without_creating_a_student_profile(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'identifier' => 'ADM100',
            'role' => 'admin',
            'status' => 'active',
            'password' => 'password',
        ]);

        $response = $this
            ->actingAs($admin)
            ->postJson('/admin/users', [
                'first_name' => 'Grace',
                'last_name' => 'Mwangi',
                'email' => 'grace.mwangi@example.test',
                'identifier' => 'STF100',
                'role' => 'staff',
                'staff_role' => 'hod',
                'department' => 'Computer Science',
                'salutation' => 'Dr.',
                'status' => 'active',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.identifier', 'STF100')
            ->assertJsonPath('user.role', 'staff');

        $this->assertDatabaseHas('users', [
            'identifier' => 'STF100',
            'role' => 'staff',
            'email' => 'grace.mwangi@example.test',
        ]);

        $this->assertDatabaseHas('staff', [
            'staff_id' => 'STF100',
            'first_name' => 'Grace',
            'last_name' => 'Mwangi',
            'role' => 'hod',
            'department' => 'Computer Science',
        ]);

        $this->assertDatabaseMissing('students', [
            'reg_number' => 'STF100',
        ]);
    }

    public function test_password_reset_requirement_blocks_login_until_reset_flow_is_completed(): void
    {
        $user = User::create([
            'name' => 'Reset Required',
            'email' => 'reset-required@example.test',
            'identifier' => 'RST100',
            'role' => 'student',
            'status' => 'active',
            'require_password_reset' => true,
            'password' => 'TempPass123',
        ]);

        $this->postJson('/login', [
            'identifier' => 'RST100',
            'password' => 'TempPass123',
        ])->assertStatus(409)
            ->assertJsonPath('next', route('portal.password.forgot', ['identifier' => 'RST100']));

        $this->withSession([
            'password_reset_flow' => [
                'identifier' => 'RST100',
                'verified_at' => now()->toIso8601String(),
            ],
        ])->postJson('/forgot-password/reset', [
            'password' => 'NewPass456',
            'password_confirmation' => 'NewPass456',
        ])->assertOk()
            ->assertJsonPath('identifier', 'RST100');

        $user->refresh();

        $this->assertFalse($user->require_password_reset);
        $this->assertTrue(Hash::check('NewPass456', $user->password));

        $this->postJson('/login', [
            'identifier' => 'RST100',
            'password' => 'NewPass456',
        ])->assertOk()
            ->assertJsonPath('identifier', 'RST100');
    }
}
