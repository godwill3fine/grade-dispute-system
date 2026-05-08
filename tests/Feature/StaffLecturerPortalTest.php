<?php

namespace Tests\Feature;

use App\Models\Dispute;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PDO;
use Tests\TestCase;

class StaffLecturerPortalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite is not enabled; install/enable it to run in-memory database tests.');
        }

        $this->artisan('migrate:fresh')->assertExitCode(0);
    }

    public function test_staff_users_are_redirected_into_the_lecturer_dashboard_and_can_open_the_shared_view(): void
    {
        $staffUser = $this->seedStaffUser('H001', 'hod');

        $this->actingAs($staffUser)
            ->get('/portal/app')
            ->assertRedirect(route('portal.lecturer.dashboard'));

        $this->actingAs($staffUser)
            ->get(route('portal.lecturer.dashboard'))
            ->assertOk()
            ->assertSee('Hod Review Access');
    }

    public function test_identify_prefers_staff_over_conflicting_student_profiles(): void
    {
        $this->seedStudent('MIX100');
        $this->seedStaffUser('MIX100', 'exam_officer');

        $this->postJson('/identify', [
            'identifier' => 'MIX100',
        ])->assertOk()
            ->assertJsonPath('status', 'existing_user')
            ->assertJsonPath('role', 'staff');

        User::query()->where('identifier', 'MIX100')->delete();

        $this->postJson('/identify', [
            'identifier' => 'MIX100',
        ])->assertOk()
            ->assertJsonPath('status', 'first_time')
            ->assertJsonPath('role', 'staff');
    }

    public function test_staff_queue_includes_both_lecturer_and_escalated_cases_for_the_same_identifier(): void
    {
        $staffUser = $this->seedStaffUser('H001', 'hod');
        $this->seedStudent('S001');

        $lecturerCase = Dispute::create([
            'student_id' => 'S001',
            'type' => 'unit',
            'unit_code' => null,
            'assigned_to' => 'H001',
            'assigned_role' => 'lecturer',
            'title' => 'Direct lecturer review',
            'description' => 'Assigned as part of teaching duties.',
            'status' => 'pending',
        ]);

        $escalatedCase = Dispute::create([
            'student_id' => 'S001',
            'type' => 'general',
            'unit_code' => null,
            'assigned_to' => 'H001',
            'assigned_role' => 'hod',
            'title' => 'Escalated department review',
            'description' => 'Assigned as HOD.',
            'status' => 'escalated',
        ]);

        $response = $this
            ->actingAs($staffUser)
            ->getJson('/lecturer/disputes/H001');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $lecturerCase->id,
                'assigned_role' => 'lecturer',
            ])
            ->assertJsonFragment([
                'id' => $escalatedCase->id,
                'assigned_role' => 'hod',
            ]);
    }

    public function test_staff_can_manage_their_own_escalated_case_but_cannot_open_another_reviewers_queue(): void
    {
        $staffUser = $this->seedStaffUser('H001', 'hod');
        $this->seedStaffUser('H002', 'exam_officer');
        $this->seedStudent('S001');

        $dispute = Dispute::create([
            'student_id' => 'S001',
            'type' => 'general',
            'unit_code' => null,
            'assigned_to' => 'H001',
            'assigned_role' => 'hod',
            'title' => 'Escalated to HOD',
            'description' => 'Needs department-level review.',
            'status' => 'escalated',
        ]);

        $this->actingAs($staffUser)
            ->postJson('/dispute/respond', [
                'dispute_id' => $dispute->id,
                'comment' => 'I am reviewing this escalated dispute now.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('dispute_logs', [
            'dispute_id' => $dispute->id,
            'action' => 'Response Added',
            'performed_by' => 'H001',
        ]);

        $this->actingAs($staffUser)
            ->postJson('/dispute/status', [
                'dispute_id' => $dispute->id,
                'status' => 'under_review',
                'comment' => 'Review is underway.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('disputes', [
            'id' => $dispute->id,
            'status' => 'under_review',
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => 'S001',
            'title' => 'Dispute Status Updated',
            'message' => 'Your dispute '.$dispute->ticket_number.' status changed to Under Review',
        ]);

        $this->actingAs($staffUser)
            ->getJson('/lecturer/disputes/H002')
            ->assertStatus(403);
    }

    private function seedStudent(string $regNumber): void
    {
        Student::create([
            'reg_number' => $regNumber,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'course' => 'BBC',
            'year' => 3,
        ]);
    }

    private function seedStaffUser(string $identifier, string $staffRole): User
    {
        DB::table('staff')->insert([
            'staff_id' => $identifier,
            'first_name' => 'Staff',
            'last_name' => $identifier,
            'role' => $staffRole,
            'department' => 'Computing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::create([
            'name' => 'Staff '.$identifier,
            'email' => strtolower($identifier).'@example.test',
            'identifier' => $identifier,
            'role' => 'staff',
            'status' => 'active',
            'password' => 'password',
        ]);
    }
}
