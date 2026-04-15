<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Dispute;
use App\Models\Student;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use PDO;
use Tests\TestCase;

class AdminManageTicketPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite is not enabled; install/enable it to run in-memory database tests.');
        }

        Artisan::call('migrate:fresh');
    }

    public function test_admin_can_open_the_manage_ticket_page(): void
    {
        $department = Department::create([
            'name' => 'Computer Science',
            'faculty' => 'COPAS',
        ]);

        Unit::create([
            'unit_code' => 'CS101',
            'unit_name' => 'Introduction to Computing',
            'department_id' => $department->id,
        ]);

        Student::create([
            'reg_number' => 'S001',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'course' => 'BBC',
            'year' => 2,
            'department' => 'Computer Science',
            'faculty' => 'COPAS',
        ]);

        $admin = User::create([
            'name' => 'Case Admin',
            'email' => 'admin@example.test',
            'identifier' => 'ADM001',
            'role' => 'admin',
            'status' => 'active',
            'password' => 'password',
        ]);

        $dispute = Dispute::create([
            'ticket_number' => 'DSP-01000',
            'student_id' => 'S001',
            'type' => 'unit',
            'unit_code' => 'CS101',
            'priority' => 'high',
            'assigned_to' => 'ADM001',
            'assigned_role' => 'admin',
            'title' => 'Calculation Error Appeal',
            'description' => 'Please review the final exam total because the submitted marks appear inconsistent.',
            'status' => 'escalated',
        ]);

        $dispute->timestamps = false;
        $dispute->forceFill([
            'created_at' => now()->subHours(36),
            'updated_at' => now()->subHours(12),
        ])->save();
        $dispute->timestamps = true;

        $response = $this
            ->actingAs($admin)
            ->get(route('portal.admin.disputes.manage', $dispute->id));

        $response
            ->assertOk()
            ->assertSee('DSP-01000')
            ->assertSee('Calculation Error Appeal')
            ->assertSee('People Involved')
            ->assertSee('Computer Science')
            ->assertSee('1.5 Days')
            ->assertSee('Activity Timeline');
    }
}
