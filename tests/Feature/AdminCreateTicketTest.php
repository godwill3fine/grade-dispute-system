<?php

namespace Tests\Feature;

use App\Http\Controllers\FrontendController;
use App\Models\Department;
use App\Models\Dispute;
use App\Models\Lecturer;
use App\Models\Notification;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Unit;
use App\Models\UnitLecturer;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use PDO;
use Tests\TestCase;

class AdminCreateTicketTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite is not enabled; install/enable it to run in-memory database tests.');
        }

        Artisan::call('migrate:fresh');
    }

    public function test_admin_can_open_the_create_ticket_page(): void
    {
        Department::create([
            'name' => 'Computer Science',
            'faculty' => 'COPAS',
        ]);

        $admin = $this->seedAdmin();

        $this->actingAs($admin);

        $view = app(FrontendController::class)->adminCreateTicket();
        $meta = $view->getData()['adminCreateTicketMeta'] ?? [];

        $this->assertSame('admin.create-ticket', $view->name());
        $this->assertNotEmpty($meta['departments'] ?? []);
        $this->assertContains('Calculation Error', $meta['issue_types'] ?? []);
    }

    public function test_admin_can_lookup_student_by_identifier_or_email(): void
    {
        $this->seedStudentWithUser(
            regNumber: 'S001',
            email: 'student@example.test',
            course: 'BBC',
            department: 'Computer Science',
            faculty: 'COPAS',
        );

        $admin = $this->seedAdmin();

        $response = $this
            ->actingAs($admin)
            ->getJson('/admin/students/lookup?q='.rawurlencode('student@example.test'));

        $response
            ->assertOk()
            ->assertJsonPath('student_id', 'S001')
            ->assertJsonPath('name', 'Test Student')
            ->assertJsonPath('course', 'BBC')
            ->assertJsonPath('department', 'Computer Science');
    }

    public function test_admin_can_create_and_route_a_unit_ticket_with_attachments(): void
    {
        Storage::fake('public');

        $department = Department::create([
            'name' => 'Computer Science',
            'faculty' => 'COPAS',
        ]);

        Unit::create([
            'unit_code' => 'CS101',
            'unit_name' => 'Introduction to Computing',
            'department_id' => $department->id,
        ]);

        $this->seedStudentWithUser(
            regNumber: 'S001',
            email: 'student@example.test',
            course: 'BBC',
            department: 'Computer Science',
            faculty: 'COPAS',
        );

        Lecturer::create([
            'lecturer_id' => 'LEC001',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'salutation' => 'Dr.',
            'department' => 'Computer Science',
        ]);

        UnitLecturer::create([
            'unit_code' => 'CS101',
            'lecturer_id' => 'LEC001',
            'course' => 'BBC',
        ]);

        $admin = $this->seedAdmin();

        $response = $this
            ->actingAs($admin)
            ->post('/admin/disputes', [
                'student_id' => 'S001',
                'type' => 'unit',
                'unit_code' => 'CS101',
                'lecturer_id' => 'LEC001',
                'department_name' => 'Computer Science',
                'issue_type' => 'Calculation Error',
                'title' => 'CS101 final exam calculation mismatch',
                'description' => 'The final exam score on the portal does not match the marked script.',
                'priority' => 'high',
                'notify_student' => '1',
                'notify_assignee' => '1',
                'route_immediately' => '1',
                'attachments' => [
                    UploadedFile::fake()->create('evidence.pdf', 180, 'application/pdf'),
                ],
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'under_review')
            ->assertJsonPath('data.assigned_to', 'LEC001')
            ->assertJsonPath('data.assigned_role', 'lecturer')
            ->assertJsonPath('data.routing_stage', 'Lecturer Review');

        $dispute = Dispute::query()->firstOrFail();

        $this->assertSame('S001', $dispute->student_id);
        $this->assertSame('LEC001', $dispute->assigned_to);
        $this->assertSame('under_review', $dispute->status);
        $this->assertSame('Lecturer Review', $dispute->assignment_name);

        $this->assertDatabaseCount('attachments', 1);
        Storage::disk('public')->assertExists($dispute->attachments()->firstOrFail()->file_path);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => 'S001',
            'dispute_id' => $dispute->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => 'LEC001',
            'dispute_id' => $dispute->id,
        ]);

        $this->assertDatabaseHas('dispute_logs', [
            'dispute_id' => $dispute->id,
            'action' => 'Ticket Created',
            'performed_by' => 'ADM001',
        ]);
    }

    public function test_admin_can_save_a_ticket_draft_to_admin_intake_without_notifications(): void
    {
        $this->seedStudentWithUser(
            regNumber: 'S001',
            email: 'student@example.test',
            course: 'BBC',
            department: 'Computer Science',
            faculty: 'COPAS',
        );

        Staff::create([
            'staff_id' => 'HOD001',
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'salutation' => 'Prof.',
            'role' => 'hod',
            'department' => 'Computer Science',
        ]);

        $admin = $this->seedAdmin();

        $response = $this
            ->actingAs($admin)
            ->post('/admin/disputes', [
                'student_id' => 'S001',
                'type' => 'general',
                'assigned_role' => 'hod',
                'department_name' => 'Computer Science',
                'title' => 'Department review needed',
                'description' => 'This should stay in intake until the documentation is complete.',
                'priority' => 'medium',
                'notify_student' => '1',
                'notify_assignee' => '1',
                'route_immediately' => '0',
                'submission_mode' => 'draft',
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.assigned_to', 'ADM001')
            ->assertJsonPath('data.assigned_role', 'admin')
            ->assertJsonPath('data.routing_stage', 'Admin Intake');

        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseHas('disputes', [
            'student_id' => 'S001',
            'assigned_to' => 'ADM001',
            'assigned_role' => 'admin',
            'status' => 'pending',
            'assignment_name' => 'Admin Intake',
        ]);
    }

    public function test_admin_create_auto_routes_by_matching_student_course_when_no_lecturer_is_selected(): void
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

        $this->seedStudentWithUser(
            regNumber: 'S001',
            email: 'student@example.test',
            course: 'BBC',
            department: 'Computer Science',
            faculty: 'COPAS',
        );

        Lecturer::create([
            'lecturer_id' => 'LEC001',
            'first_name' => 'Alan',
            'last_name' => 'Turing',
            'salutation' => 'Dr.',
            'department' => 'Computer Science',
        ]);

        Lecturer::create([
            'lecturer_id' => 'LEC002',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'salutation' => 'Dr.',
            'department' => 'Computer Science',
        ]);

        UnitLecturer::create([
            'unit_code' => 'CS101',
            'lecturer_id' => 'LEC001',
            'course' => 'DIT',
        ]);

        UnitLecturer::create([
            'unit_code' => 'CS101',
            'lecturer_id' => 'LEC002',
            'course' => 'BBC',
        ]);

        $admin = $this->seedAdmin();

        $response = $this
            ->actingAs($admin)
            ->post('/admin/disputes', [
                'student_id' => 'S001',
                'type' => 'unit',
                'unit_code' => 'CS101',
                'title' => 'Auto-route this unit case',
                'description' => 'Route this ticket using the student course mapping.',
                'priority' => 'medium',
                'route_immediately' => '1',
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.assigned_to', 'LEC002')
            ->assertJsonPath('data.assigned_role', 'lecturer');
    }

    private function seedAdmin(): User
    {
        return User::create([
            'name' => 'Case Admin',
            'email' => 'admin@example.test',
            'identifier' => 'ADM001',
            'role' => 'admin',
            'status' => 'active',
            'password' => 'password',
        ]);
    }

    private function seedStudentWithUser(
        string $regNumber,
        string $email,
        string $course,
        string $department,
        string $faculty,
    ): void {
        Student::create([
            'reg_number' => $regNumber,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'course' => $course,
            'year' => 2,
            'department' => $department,
            'faculty' => $faculty,
        ]);

        User::create([
            'name' => 'Test Student',
            'email' => $email,
            'identifier' => $regNumber,
            'role' => 'student',
            'status' => 'active',
            'password' => 'password',
        ]);
    }
}
