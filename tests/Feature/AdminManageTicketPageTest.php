<?php

namespace Tests\Feature;

use App\Http\Controllers\FrontendController;
use App\Models\Department;
use App\Models\Dispute;
use App\Models\DisputeLog;
use App\Models\Lecturer;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Unit;
use App\Models\UnitLecturer;
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

        $this->actingAs($admin);

        $view = app(FrontendController::class)->adminManageTicket($dispute->id);
        $ticket = $view->getData()['adminManageTicket'] ?? [];

        $this->assertSame('admin.manage-ticket', $view->name());
        $this->assertSame('DSP-01000', $ticket['ticket_number'] ?? null);
        $this->assertSame('Calculation Error Appeal', $ticket['issue_type'] ?? null);
        $this->assertSame('Computer Science', $ticket['department_name'] ?? null);
        $this->assertSame('1.5 Days', $ticket['time_open_label'] ?? null);
        $this->assertNotEmpty($ticket['people'] ?? []);
        $this->assertNotEmpty($ticket['timeline'] ?? []);
        $this->assertNotEmpty($ticket['print_case_overview_rows'] ?? []);
        $this->assertNotEmpty($ticket['print_student_profile_rows'] ?? []);
        $this->assertNotEmpty($ticket['print_academic_context_rows'] ?? []);
        $this->assertSame('Case Admin', $ticket['print_generated_by_name'] ?? null);
    }

    public function test_admin_manage_ticket_formats_overdue_values_to_one_decimal_place(): void
    {
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
            'type' => 'general',
            'priority' => 'high',
            'assigned_to' => 'ADM001',
            'assigned_role' => 'admin',
            'title' => 'Calculation Error Appeal',
            'description' => 'Please review the final exam total because the submitted marks appear inconsistent.',
            'status' => 'pending',
        ]);

        $dispute->timestamps = false;
        $dispute->forceFill([
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ])->save();
        $dispute->timestamps = true;

        DisputeLog::query()
            ->where('dispute_id', $dispute->id)
            ->get()
            ->each(function (DisputeLog $log): void {
                $log->timestamps = false;
                $log->forceFill([
                    'created_at' => now()->subDays(5),
                    'updated_at' => now()->subDays(5),
                ])->save();
                $log->timestamps = true;
            });

        $this->actingAs($admin);

        $view = app(FrontendController::class)->adminManageTicket($dispute->id);
        $ticket = $view->getData()['adminManageTicket'] ?? [];

        $this->assertSame('Overdue by 3.0 Days', $ticket['sla_label'] ?? null);
        $this->assertSame('SLA exceeded by 3.0 Days', $ticket['time_open_meta'] ?? null);
    }

    public function test_manage_ticket_lists_all_lecturers_and_explicit_route_destinations(): void
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

        Lecturer::create([
            'lecturer_id' => 'LEC001',
            'full_name' => 'Dr Alpha',
            'department' => 'Computer Science',
        ]);

        Lecturer::create([
            'lecturer_id' => 'LEC099',
            'full_name' => 'Dr Zeta',
            'department' => 'Mathematics',
        ]);

        UnitLecturer::create([
            'unit_code' => 'CS101',
            'lecturer_id' => 'LEC001',
            'course' => 'BBC',
        ]);

        Staff::create([
            'staff_id' => 'EX001',
            'full_name' => 'Ms Exam',
            'role' => 'exam_officer',
            'department' => 'Computer Science',
        ]);

        Staff::create([
            'staff_id' => 'HOD001',
            'full_name' => 'Prof HOD',
            'role' => 'hod',
            'department' => 'Computer Science',
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

        $this->actingAs($admin);

        $view = app(FrontendController::class)->adminManageTicket($dispute->id);
        $ticket = $view->getData()['adminManageTicket'] ?? [];

        $this->assertSame('admin', $ticket['selected_routing'] ?? null);
        $this->assertContains('LEC001', array_column($ticket['lecturer_options'] ?? [], 'identifier'));
        $this->assertContains('LEC099', array_column($ticket['lecturer_options'] ?? [], 'identifier'));
        $this->assertSame(
            ['lecturer', 'exam_officer', 'hod', 'admin', 'appeals_panel'],
            array_column($ticket['route_options'] ?? [], 'value')
        );
        $this->assertNotEmpty($ticket['routing_help_text'] ?? '');
        $this->assertStringContainsString('course BBC', $ticket['print_routing_narrative'] ?? '');
        $this->assertStringContainsString('LEC001', $ticket['print_routing_narrative'] ?? '');
        $this->assertCount(1, array_filter($ticket['print_assignment_snapshot'] ?? [], fn (array $row): bool => !empty($row['is_student_course'])));
    }

    public function test_admin_can_mark_a_ticket_as_resolved(): void
    {
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
            'ticket_number' => 'DSP-01001',
            'student_id' => 'S001',
            'type' => 'general',
            'priority' => 'high',
            'assigned_to' => 'ADM001',
            'assigned_role' => 'admin',
            'title' => 'Final Grade Review',
            'description' => 'Please confirm the final grade tally.',
            'status' => 'escalated',
        ]);

        $response = $this
            ->actingAs($admin)
            ->postJson('/admin/disputes/'.$dispute->id.'/manage', [
                'status' => 'resolved',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('changed', true)
            ->assertJsonPath('data.status', 'resolved');

        $dispute->refresh();

        $this->assertSame('resolved', $dispute->status);
        $this->assertNotNull($dispute->resolved_at);
        $this->assertDatabaseHas('dispute_logs', [
            'dispute_id' => $dispute->id,
            'action' => 'Status Updated',
            'old_status' => 'escalated',
            'new_status' => 'resolved',
            'performed_by' => 'ADM001',
        ]);
    }

    public function test_admin_can_reassign_a_ticket_to_any_lecturer(): void
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

        Lecturer::create([
            'lecturer_id' => 'LEC001',
            'full_name' => 'Dr Alpha',
            'department' => 'Computer Science',
        ]);

        Lecturer::create([
            'lecturer_id' => 'LEC002',
            'full_name' => 'Dr Beta',
            'department' => 'Computer Science',
        ]);

        Lecturer::create([
            'lecturer_id' => 'LEC003',
            'full_name' => 'Dr Gamma',
            'department' => 'Mathematics',
        ]);

        UnitLecturer::create([
            'unit_code' => 'CS101',
            'lecturer_id' => 'LEC001',
            'course' => 'BBC',
        ]);

        UnitLecturer::create([
            'unit_code' => 'CS101',
            'lecturer_id' => 'LEC002',
            'course' => 'BBC',
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
            'ticket_number' => 'DSP-01002',
            'student_id' => 'S001',
            'type' => 'unit',
            'unit_code' => 'CS101',
            'priority' => 'medium',
            'assigned_to' => 'ADM001',
            'assigned_role' => 'admin',
            'title' => 'CAT Mark Review',
            'description' => 'The CAT score seems lower than expected.',
            'status' => 'escalated',
        ]);

        $response = $this
            ->actingAs($admin)
            ->postJson('/admin/disputes/'.$dispute->id.'/manage', [
                'route_destination' => 'lecturer',
                'lecturer_id' => 'LEC003',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('changed', true)
            ->assertJsonPath('data.assigned_to', 'LEC003')
            ->assertJsonPath('data.assigned_role', 'lecturer')
            ->assertJsonPath('data.status', 'under_review')
            ->assertJsonPath('data.routing_stage', 'Lecturer Review')
            ->assertJsonPath('data.route_destination', 'lecturer');

        $this->assertDatabaseHas('disputes', [
            'id' => $dispute->id,
            'assigned_to' => 'LEC003',
            'assigned_role' => 'lecturer',
            'status' => 'under_review',
            'assignment_name' => 'Lecturer Review',
        ]);

        $this->assertDatabaseHas('dispute_logs', [
            'dispute_id' => $dispute->id,
            'action' => 'Lecturer Reassigned',
            'performed_by' => 'ADM001',
        ]);
    }

    public function test_admin_can_route_a_ticket_directly_to_the_exam_officer(): void
    {
        Student::create([
            'reg_number' => 'S001',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'course' => 'BBC',
            'year' => 2,
            'department' => 'Computer Science',
            'faculty' => 'COPAS',
        ]);

        Staff::create([
            'staff_id' => 'EX001',
            'full_name' => 'Ms Exam',
            'role' => 'exam_officer',
            'department' => 'Computer Science',
        ]);

        Staff::create([
            'staff_id' => 'HOD001',
            'full_name' => 'Prof HOD',
            'role' => 'hod',
            'department' => 'Computer Science',
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
            'ticket_number' => 'DSP-01003',
            'student_id' => 'S001',
            'type' => 'general',
            'priority' => 'medium',
            'assigned_to' => 'ADM001',
            'assigned_role' => 'admin',
            'title' => 'Department Review Request',
            'description' => 'Please escalate this case for board review.',
            'status' => 'pending',
        ]);

        $response = $this
            ->actingAs($admin)
            ->postJson('/admin/disputes/'.$dispute->id.'/manage', [
                'route_destination' => 'exam_officer',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('changed', true)
            ->assertJsonPath('data.assigned_to', 'EX001')
            ->assertJsonPath('data.assigned_role', 'exam_officer')
            ->assertJsonPath('data.status', 'escalated')
            ->assertJsonPath('data.routing_stage', 'Exam Officer Review')
            ->assertJsonPath('data.route_destination', 'exam_officer');

        $dispute->refresh();

        $this->assertSame('EX001', $dispute->assigned_to);
        $this->assertSame('exam_officer', $dispute->assigned_role);
        $this->assertSame('escalated', $dispute->status);
        $this->assertSame('Exam Officer Review', $dispute->assignment_name);
        $this->assertNotNull($dispute->escalated_at);

        $this->assertTrue(
            DisputeLog::query()
                ->where('dispute_id', $dispute->id)
                ->where('action', 'Routing Updated')
                ->exists()
        );
    }

    public function test_admin_can_route_a_ticket_directly_to_the_head_of_department(): void
    {
        Student::create([
            'reg_number' => 'S001',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'course' => 'BBC',
            'year' => 2,
            'department' => 'Computer Science',
            'faculty' => 'COPAS',
        ]);

        Staff::create([
            'staff_id' => 'EX001',
            'full_name' => 'Ms Exam',
            'role' => 'exam_officer',
            'department' => 'Computer Science',
        ]);

        Staff::create([
            'staff_id' => 'HOD001',
            'full_name' => 'Prof HOD',
            'role' => 'hod',
            'department' => 'Computer Science',
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
            'ticket_number' => 'DSP-01004',
            'student_id' => 'S001',
            'type' => 'general',
            'priority' => 'medium',
            'assigned_to' => 'ADM001',
            'assigned_role' => 'admin',
            'title' => 'Department Review Request',
            'description' => 'Please escalate this case to the HOD directly.',
            'status' => 'pending',
        ]);

        $response = $this
            ->actingAs($admin)
            ->postJson('/admin/disputes/'.$dispute->id.'/manage', [
                'route_destination' => 'hod',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('changed', true)
            ->assertJsonPath('data.assigned_to', 'HOD001')
            ->assertJsonPath('data.assigned_role', 'hod')
            ->assertJsonPath('data.status', 'escalated')
            ->assertJsonPath('data.routing_stage', 'Head of Department Review')
            ->assertJsonPath('data.route_destination', 'hod');

        $dispute->refresh();

        $this->assertSame('HOD001', $dispute->assigned_to);
        $this->assertSame('hod', $dispute->assigned_role);
        $this->assertSame('escalated', $dispute->status);
        $this->assertSame('Head of Department Review', $dispute->assignment_name);
        $this->assertNotNull($dispute->escalated_at);
    }
}
