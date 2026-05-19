<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use PDO;
use Tests\TestCase;

class StudentProfilePageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite is not enabled; install/enable it to run in-memory database tests.');
        }

        Artisan::call('migrate:fresh');
    }

    public function test_student_profile_reads_department_and_faculty_from_the_student_record(): void
    {
        Course::create([
            'code' => 'BBC',
            'name' => 'B.Sc. Business Computing',
            'faculty' => 'College of Pure and Applied Science',
            'faculty_code' => 'COPAS',
        ]);

        Student::create([
            'reg_number' => 'S001',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'course' => 'BBC',
            'year' => 4,
            'department' => 'SCIT',
            'faculty' => 'COPAS',
        ]);

        $user = User::create([
            'name' => 'Test Student',
            'email' => 's001@example.test',
            'identifier' => 'S001',
            'role' => 'student',
            'status' => 'active',
            'password' => 'password',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('portal.student.profile'));

        $response
            ->assertOk()
            ->assertSee('B.Sc. Business Computing (BBC)')
            ->assertSee('SCIT')
            ->assertSee('COPAS');
    }

    public function test_student_submit_dispute_shows_the_full_course_name_from_the_catalog(): void
    {
        Course::create([
            'code' => 'BBC',
            'name' => 'B.Sc. Business Computing',
            'faculty' => 'College of Pure and Applied Science',
            'faculty_code' => 'COPAS',
        ]);

        Student::create([
            'reg_number' => 'S001',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'course' => 'BBC',
            'year' => 4,
            'department' => 'SCIT',
            'faculty' => 'COPAS',
        ]);

        $user = User::create([
            'name' => 'Test Student',
            'email' => 's001@example.test',
            'identifier' => 'S001',
            'role' => 'student',
            'status' => 'active',
            'password' => 'password',
        ]);

        $this
            ->actingAs($user)
            ->get(route('portal.student.submit'))
            ->assertOk()
            ->assertSee('B.Sc. Business Computing (BBC)')
            ->assertSee('Course code: BBC.');
    }
}
