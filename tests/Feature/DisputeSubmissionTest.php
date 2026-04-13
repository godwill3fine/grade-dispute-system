<?php

namespace Tests\Feature;

use App\Models\Dispute;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PDO;
use Tests\TestCase;

class DisputeSubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite is not enabled; install/enable it to run in-memory database tests.');
        }

        $this->artisan('migrate:fresh')->assertExitCode(0);
    }

    public function test_it_routes_unit_disputes_by_unit_and_exposes_the_student_course_to_the_lecturer(): void
    {
        $this->seedStudentWithUser('S001', 'BBC');
        $this->seedUnit('U001');
        $this->seedLecturer('L001', true);
        $this->seedUnitLecturer('U001', 'L001', 'DIT');

        $response = $this
            ->actingAs(User::where('identifier', 'S001')->firstOrFail())
            ->postJson('/disputes', [
                'student_id' => 'S001',
                'type' => 'unit',
                'unit_code' => 'U001',
                'title' => 'Grade mismatch',
                'description' => 'The grade recorded for this unit looks incorrect.',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.assigned_to', 'L001');

        $this->assertDatabaseHas('disputes', [
            'student_id' => 'S001',
            'unit_code' => 'U001',
            'assigned_to' => 'L001',
            'assigned_role' => 'lecturer',
        ]);

        $lecturerQueue = $this
            ->actingAs(User::where('identifier', 'L001')->firstOrFail())
            ->getJson('/lecturer/disputes/L001');

        $lecturerQueue
            ->assertOk()
            ->assertJsonFragment([
                'student_id' => 'S001',
                'student_course' => 'BBC',
            ]);
    }

    public function test_it_allows_unit_dispute_submission_when_the_student_course_is_blank(): void
    {
        $this->seedStudentWithUser('S001', '');
        $this->seedUnit('U001');
        $this->seedLecturer('L001');
        $this->seedUnitLecturer('U001', 'L001', 'BBC');

        $response = $this
            ->actingAs(User::where('identifier', 'S001')->firstOrFail())
            ->postJson('/disputes', [
                'student_id' => 'S001',
                'type' => 'unit',
                'unit_code' => 'U001',
                'title' => 'Cat score issue',
                'description' => 'The CAT mark on the portal does not match my script.',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.assigned_to', 'L001');

        $this->assertDatabaseHas('disputes', [
            'student_id' => 'S001',
            'unit_code' => 'U001',
            'assigned_to' => 'L001',
            'assigned_role' => 'lecturer',
        ]);
    }

    public function test_it_requires_a_single_active_lecturer_per_unit_until_year_based_routing_exists(): void
    {
        $this->seedStudentWithUser('S001', 'BBC');
        $this->seedUnit('U001');
        $this->seedLecturer('L001');
        $this->seedLecturer('L002');
        $this->seedUnitLecturer('U001', 'L001', 'BBC');
        $this->seedUnitLecturer('U001', 'L002', 'DIT');

        $response = $this
            ->actingAs(User::where('identifier', 'S001')->firstOrFail())
            ->postJson('/disputes', [
                'student_id' => 'S001',
                'type' => 'unit',
                'unit_code' => 'U001',
                'title' => 'Assignment grade issue',
                'description' => 'I need a review of this grade.',
            ]);

        $response
            ->assertStatus(409)
            ->assertJsonPath(
                'error',
                'Multiple lecturers are assigned to this unit. Please ask admin to keep one active lecturer per unit until year-based routing is added.',
            );

        $this->assertDatabaseCount('disputes', 0);
    }

    public function test_student_disputes_endpoint_accepts_registration_numbers_with_slashes(): void
    {
        $this->seedStudentWithUser('SCT222-0163/2022', 'BBC');

        Dispute::create([
            'student_id' => 'SCT222-0163/2022',
            'type' => 'general',
            'unit_code' => null,
            'priority' => 'medium',
            'assigned_to' => 'A001',
            'assigned_role' => 'admin',
            'title' => 'Portal grade mismatch',
            'description' => 'I need this reviewed.',
            'status' => 'pending',
        ]);

        $response = $this
            ->actingAs(User::where('identifier', 'SCT222-0163/2022')->firstOrFail())
            ->getJson('/student/disputes/'.rawurlencode('SCT222-0163/2022'));

        $response
            ->assertOk()
            ->assertJsonFragment([
                'student_id' => 'SCT222-0163/2022',
                'student_course' => 'BBC',
            ]);
    }

    private function seedStudentWithUser(string $regNumber, string $course): void
    {
        Student::create([
            'reg_number' => $regNumber,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'course' => $course,
            'year' => 2,
        ]);

        User::create([
            'name' => 'Test Student',
            'email' => strtolower($regNumber).'@example.test',
            'identifier' => $regNumber,
            'role' => 'student',
            'status' => 'active',
            'password' => 'password',
        ]);
    }

    private function seedUnit(string $unitCode): void
    {
        DB::table('units')->insert([
            'unit_code' => $unitCode,
            'unit_name' => 'Test Unit '.$unitCode,
            'department_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedLecturer(string $lecturerId, bool $withUser = false): void
    {
        DB::table('lecturers')->insert([
            'lecturer_id' => $lecturerId,
            'first_name' => 'Lecturer',
            'last_name' => $lecturerId,
            'salutation' => 'Mr.',
            'department' => 'Computing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($withUser) {
            User::create([
                'name' => 'Lecturer '.$lecturerId,
                'email' => strtolower($lecturerId).'@example.test',
                'identifier' => $lecturerId,
                'role' => 'lecturer',
                'status' => 'active',
                'password' => 'password',
            ]);
        }
    }

    private function seedUnitLecturer(string $unitCode, string $lecturerId, string $course): void
    {
        DB::table('unit_lecturers')->insert([
            'unit_code' => $unitCode,
            'lecturer_id' => $lecturerId,
            'course' => $course,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
