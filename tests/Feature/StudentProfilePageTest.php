<?php

namespace Tests\Feature;

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
            ->assertSee('SCIT')
            ->assertSee('COPAS');
    }
}
