<?php

namespace Tests\Feature;

use App\Models\Dispute;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use PDO;
use Tests\TestCase;

class EscalateDisputesCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite is not enabled; install/enable it to run in-memory database tests.');
        }

        $this->artisan('migrate:fresh')->assertExitCode(0);
    }

    public function test_it_escalates_pending_dispute_to_hod_after_three_days(): void
    {
        $this->seedStudent();
        $this->seedStaff();

        $dispute = Dispute::create([
            'student_id' => 'S001',
            'type' => 'general',
            'unit_code' => null,
            'assigned_to' => 'L001',
            'assigned_role' => 'lecturer',
            'title' => 'Grade mismatch',
            'description' => 'My grade appears incorrect.',
            'status' => 'pending',
        ]);

        DB::table('disputes')
            ->where('id', $dispute->id)
            ->update(['updated_at' => now()->subDays(3)]);

        $this->artisan('app:escalate-disputes')->assertExitCode(0);

        $this->assertDatabaseHas('disputes', [
            'id' => $dispute->id,
            'assigned_to' => 'H001',
            'assigned_role' => 'hod',
            'status' => 'escalated',
        ]);

        $this->assertDatabaseHas('dispute_logs', [
            'dispute_id' => $dispute->id,
            'performed_by' => 'system',
            'action' => 'Automatically escalated to HOD',
        ]);
    }

    public function test_it_escalates_hod_dispute_to_admin_after_six_days(): void
    {
        $this->seedStudent();
        $this->seedStaff();

        $dispute = Dispute::create([
            'student_id' => 'S001',
            'type' => 'general',
            'unit_code' => null,
            'assigned_to' => 'H001',
            'assigned_role' => 'hod',
            'title' => 'Delayed response',
            'description' => 'No response for several days.',
            'status' => 'escalated',
        ]);

        DB::table('disputes')
            ->where('id', $dispute->id)
            ->update(['updated_at' => now()->subDays(6)]);

        $this->artisan('app:escalate-disputes')->assertExitCode(0);

        $this->assertDatabaseHas('disputes', [
            'id' => $dispute->id,
            'assigned_to' => 'A001',
            'assigned_role' => 'admin',
            'status' => 'escalated',
        ]);

        $this->assertDatabaseHas('dispute_logs', [
            'dispute_id' => $dispute->id,
            'performed_by' => 'system',
            'action' => 'Automatically escalated to Admin',
        ]);
    }

    private function seedStudent(): void
    {
        Student::create([
            'reg_number' => 'S001',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'course' => 'BSc CS',
            'year' => 2,
        ]);
    }

    private function seedStaff(): void
    {
        DB::table('staff')->insert([
            [
                'staff_id' => 'H001',
                'first_name' => 'Head',
                'last_name' => 'of Department',
                'role' => 'hod',
                'department' => 'Computing',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'staff_id' => 'A001',
                'first_name' => 'System',
                'last_name' => 'Admin',
                'role' => 'admin',
                'department' => 'Computing',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
