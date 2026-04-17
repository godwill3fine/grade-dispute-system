<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use PDO;
use Tests\TestCase;

class AdminUserImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite is not enabled; install/enable it to run in-memory database tests.');
        }

        Artisan::call('migrate:fresh');
    }

    public function test_admin_can_import_users_from_csv(): void
    {
        $admin = User::create([
            'name' => 'Import Admin',
            'email' => 'import-admin@example.test',
            'identifier' => 'ADM999',
            'role' => 'admin',
            'status' => 'active',
            'password' => 'password',
        ]);

        $csv = implode("\n", [
            'identifier,role,first_name,last_name,email,department,faculty,course,year,salutation,status,password',
            'S001,student,John,Smith,john.smith@example.test,Computer Science,COPAS,BBC,2,,active,Secret123',
            'LEC001,lecturer,Jane,Doe,jane.doe@example.test,Computer Science,COPAS,,,Dr.,active,Teach123',
            'ADM001,admin,Case,Owner,case.owner@example.test,Administration,,,,Mr.,active,Admin123',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post('/admin/users/import', [
                'import_file' => UploadedFile::fake()->createWithContent('users.csv', $csv),
            ], [
                'Accept' => 'application/json',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('summary.created_count', 3)
            ->assertJsonPath('summary.skipped_count', 0)
            ->assertJsonPath('summary.error_count', 0)
            ->assertJsonPath('summary.default_password_count', 0);

        $this->assertDatabaseHas('users', [
            'identifier' => 'S001',
            'role' => 'student',
            'email' => 'john.smith@example.test',
        ]);

        $this->assertDatabaseHas('users', [
            'identifier' => 'LEC001',
            'role' => 'lecturer',
            'email' => 'jane.doe@example.test',
        ]);

        $this->assertDatabaseHas('users', [
            'identifier' => 'ADM001',
            'role' => 'admin',
            'email' => 'case.owner@example.test',
        ]);

        $this->assertDatabaseHas('students', [
            'reg_number' => 'S001',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'course' => 'BBC',
            'year' => 2,
            'department' => 'Computer Science',
            'faculty' => 'COPAS',
        ]);

        $this->assertDatabaseHas('lecturers', [
            'lecturer_id' => 'LEC001',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'department' => 'Computer Science',
        ]);

        $this->assertDatabaseHas('staff', [
            'staff_id' => 'ADM001',
            'first_name' => 'Case',
            'last_name' => 'Owner',
            'role' => 'admin',
            'department' => 'Administration',
        ]);

        $this->assertDatabaseHas('departments', [
            'name' => 'Computer Science',
            'faculty' => 'COPAS',
        ]);
    }

    public function test_import_skips_existing_users_and_defaults_missing_passwords_to_identifier(): void
    {
        $admin = User::create([
            'name' => 'Import Admin',
            'email' => 'import-admin@example.test',
            'identifier' => 'ADM999',
            'role' => 'admin',
            'status' => 'active',
            'password' => 'password',
        ]);

        User::create([
            'name' => 'Existing Student',
            'email' => 'existing.student@example.test',
            'identifier' => 'S001',
            'role' => 'student',
            'status' => 'active',
            'password' => 'password',
        ]);

        Student::create([
            'reg_number' => 'S001',
            'first_name' => 'Existing',
            'last_name' => 'Student',
            'course' => 'BBC',
            'year' => 2,
            'department' => 'Computer Science',
            'faculty' => 'COPAS',
        ]);

        $csv = implode("\n", [
            'identifier,role,first_name,last_name,email,department,faculty,course,year',
            'S001,student,John,Smith,existing.student@example.test,Computer Science,COPAS,BBC,2',
            'LEC200,lecturer,Amy,Wong,amy.wong@example.test,Information Technology,COPAS,,',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post('/admin/users/import', [
                'import_file' => UploadedFile::fake()->createWithContent('users.csv', $csv),
            ], [
                'Accept' => 'application/json',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('summary.created_count', 1)
            ->assertJsonPath('summary.skipped_count', 1)
            ->assertJsonPath('summary.error_count', 0)
            ->assertJsonPath('summary.default_password_count', 1);

        $importedLecturer = User::query()->where('identifier', 'LEC200')->first();

        $this->assertNotNull($importedLecturer);
        $this->assertTrue(Hash::check('LEC200', $importedLecturer->password));

        $this->assertDatabaseHas('lecturers', [
            'lecturer_id' => 'LEC200',
            'first_name' => 'Amy',
            'last_name' => 'Wong',
            'department' => 'Information Technology',
        ]);
    }

    public function test_admin_can_import_staff_users_without_creating_student_profiles(): void
    {
        $admin = User::create([
            'name' => 'Import Admin',
            'email' => 'import-admin@example.test',
            'identifier' => 'ADM999',
            'role' => 'admin',
            'status' => 'active',
            'password' => 'password',
        ]);

        $csv = implode("\n", [
            'identifier,role,staff_role,first_name,last_name,email,department,salutation,status',
            'STF200,staff,exam_officer,Grace,Mwangi,grace.mwangi@example.test,Computer Science,Dr.,active',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post('/admin/users/import', [
                'import_file' => UploadedFile::fake()->createWithContent('staff-users.csv', $csv),
            ], [
                'Accept' => 'application/json',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('summary.created_count', 1)
            ->assertJsonPath('summary.skipped_count', 0)
            ->assertJsonPath('summary.error_count', 0);

        $this->assertDatabaseHas('users', [
            'identifier' => 'STF200',
            'role' => 'staff',
            'email' => 'grace.mwangi@example.test',
        ]);

        $this->assertDatabaseHas('staff', [
            'staff_id' => 'STF200',
            'first_name' => 'Grace',
            'last_name' => 'Mwangi',
            'role' => 'exam_officer',
            'department' => 'Computer Science',
        ]);

        $this->assertDatabaseMissing('students', [
            'reg_number' => 'STF200',
        ]);
    }
}
