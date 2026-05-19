<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Unit;
use App\Models\UnitLecturer;
use Illuminate\Database\Seeder;

class AcademicCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $departmentIds = $this->seedDepartments();

        $this->seedCourses();
        $this->seedUnits($departmentIds);
        $this->seedLecturers();
        $this->seedTeachingAssignments();
        $this->seedStudents();
    }

    private function seedDepartments(): array
    {
        $departmentIds = [];

        foreach ($this->faculties() as $code => $name) {
            $department = Department::query()->updateOrCreate(
                ['name' => $name],
                ['faculty' => $code !== 'COMMON' ? $code : null]
            );

            $departmentIds[$code] = $department->id;
        }

        return $departmentIds;
    }

    private function seedCourses(): void
    {
        foreach ($this->courses() as $course) {
            Course::query()->updateOrCreate(
                ['code' => $course['code']],
                [
                    'name' => $course['name'],
                    'faculty' => $course['faculty'],
                    'faculty_code' => $course['faculty_code'],
                ]
            );
        }
    }

    private function seedUnits(array $departmentIds): void
    {
        foreach ($this->units() as $unit) {
            Unit::query()->updateOrCreate(
                ['unit_code' => $unit['code']],
                [
                    'unit_name' => $unit['name'],
                    'department_id' => $departmentIds[$unit['faculty_code']] ?? null,
                ]
            );
        }
    }

    private function seedLecturers(): void
    {
        foreach ($this->lecturers() as $lecturer) {
            [$firstName, $lastName] = $this->splitName($lecturer['name']);

            Lecturer::query()->updateOrCreate(
                ['lecturer_id' => $lecturer['id']],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'salutation' => null,
                    'department' => $lecturer['department'],
                ]
            );
        }
    }

    private function seedTeachingAssignments(): void
    {
        foreach ($this->teachingAssignments() as $assignment) {
            UnitLecturer::query()->updateOrCreate(
                [
                    'unit_code' => $assignment['unit_code'],
                    'lecturer_id' => $assignment['lecturer_id'],
                    'course' => $assignment['course'],
                ],
                []
            );
        }

        $courseCodes = collect($this->courses())
            ->pluck('code')
            ->reject(fn (string $code): bool => $code === 'COMMON')
            ->values();

        // Common units should route for every student course.
        foreach (['CILS 2101', 'HRD 2102'] as $unitCode) {
            foreach ($courseCodes as $courseCode) {
                UnitLecturer::query()->updateOrCreate(
                    [
                        'unit_code' => $unitCode,
                        'lecturer_id' => 'COM-LEC-57',
                        'course' => $courseCode,
                    ],
                    []
                );
            }
        }
    }

    private function seedStudents(): void
    {
        $courses = collect($this->courses())->keyBy('code');

        foreach ($this->students() as $student) {
            [$firstName, $lastName] = $this->splitName($student['name']);
            $course = $courses->get($student['course']);

            Student::query()->updateOrCreate(
                ['reg_number' => $student['reg_number']],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'course' => $student['course'],
                    'year' => $this->studyYearFromRegistration($student['reg_number']),
                    'department' => $course['name'] ?? null,
                    'faculty' => $course['faculty'] ?? null,
                ]
            );
        }
    }

    private function faculties(): array
    {
        return [
            'COHES' => 'College of Health Sciences',
            'SHRD' => 'School of Human Resource Development',
            'CAS' => 'Faculty of Agriculture',
            'COETEC' => 'College of Engineering Technology',
            'COPAS' => 'College of Pure and Applied Science',
            'COMMON' => 'University Common Units',
        ];
    }

    private function courses(): array
    {
        return [
            ['code' => 'BPH', 'name' => 'B.Sc. Public Health', 'faculty' => 'College of Health Sciences', 'faculty_code' => 'COHES'],
            ['code' => 'BCH', 'name' => 'B.Sc. Clinical Health', 'faculty' => 'College of Health Sciences', 'faculty_code' => 'COHES'],
            ['code' => 'BCOM', 'name' => 'B.Sc. Business Commerce', 'faculty' => 'School of Human Resource Development', 'faculty_code' => 'SHRD'],
            ['code' => 'BSCM', 'name' => 'B.Sc. Supply Chain Management', 'faculty' => 'School of Human Resource Development', 'faculty_code' => 'SHRD'],
            ['code' => 'MFSN', 'name' => 'M.Sc. Food Science and Nutrition', 'faculty' => 'Faculty of Agriculture', 'faculty_code' => 'CAS'],
            ['code' => 'BLRPM', 'name' => 'B.Sc. Land Resource Planning and Management', 'faculty' => 'Faculty of Agriculture', 'faculty_code' => 'CAS'],
            ['code' => 'BAT', 'name' => 'B.Sc. Architectural Technology', 'faculty' => 'College of Engineering Technology', 'faculty_code' => 'COETEC'],
            ['code' => 'BCE', 'name' => 'B.Sc. Civil Engineering', 'faculty' => 'College of Engineering Technology', 'faculty_code' => 'COETEC'],
            ['code' => 'BCS', 'name' => 'B.Sc. Computer Science', 'faculty' => 'College of Pure and Applied Science', 'faculty_code' => 'COPAS'],
            ['code' => 'BIT', 'name' => 'B.Sc. Information Technology', 'faculty' => 'College of Pure and Applied Science', 'faculty_code' => 'COPAS'],
            ['code' => 'BBC', 'name' => 'B.Sc. Business Computing', 'faculty' => 'College of Pure and Applied Science', 'faculty_code' => 'COPAS'],
            ['code' => 'COMMON', 'name' => 'Common Units', 'faculty' => 'University Common Units', 'faculty_code' => null],
        ];
    }

    private function units(): array
    {
        return [
            ['code' => 'ICS 2102', 'name' => 'Medical Physics I', 'faculty_code' => 'COHES'],
            ['code' => 'ICM 2111', 'name' => 'General Anatomy', 'faculty_code' => 'COHES'],
            ['code' => 'HBC 2103', 'name' => 'Mathematics for Business', 'faculty_code' => 'SHRD'],
            ['code' => 'BIT 2119', 'name' => 'Management Information System', 'faculty_code' => 'SHRD'],
            ['code' => 'AFP 2101', 'name' => 'Introduction to Human Nutrition and Dietetics', 'faculty_code' => 'CAS'],
            ['code' => 'ALP 2409', 'name' => 'Urban Planning II', 'faculty_code' => 'CAS'],
            ['code' => 'ABL 2188', 'name' => 'Environmental Geomatics', 'faculty_code' => 'COETEC'],
            ['code' => 'ICS 2174', 'name' => 'Introduction to Computer Science', 'faculty_code' => 'COETEC'],
            ['code' => 'ICS 2101', 'name' => 'Introduction to Programming', 'faculty_code' => 'COPAS'],
            ['code' => 'BIT 2305', 'name' => 'Human Computer Interaction', 'faculty_code' => 'COPAS'],
            ['code' => 'SMA 2104', 'name' => 'Mathematics for Science', 'faculty_code' => 'COPAS'],
            ['code' => 'CILS 2101', 'name' => 'Communication and Information Literacy Skills', 'faculty_code' => 'COMMON'],
            ['code' => 'HRD 2102', 'name' => 'Development Studies', 'faculty_code' => 'COMMON'],
        ];
    }

    private function lecturers(): array
    {
        return [
            ['id' => 'COHES-LEC001', 'name' => 'John Kimutai', 'department' => 'College of Health Sciences'],
            ['id' => 'COHES-LEC012', 'name' => 'Winner Wekesa', 'department' => 'College of Health Sciences'],
            ['id' => 'HRD-LEC002', 'name' => 'Gordon Ochieng', 'department' => 'School of Human Resource Development'],
            ['id' => 'HRD-LEC15', 'name' => 'Daniel Olam', 'department' => 'School of Human Resource Development'],
            ['id' => 'CET-LEC008', 'name' => 'Harry Hassan', 'department' => 'College of Engineering Technology'],
            ['id' => 'SCIT-LEC004', 'name' => 'Leslie Simiyu', 'department' => 'College of Pure and Applied Science'],
            ['id' => 'COM-LEC-57', 'name' => 'Samuel Kipruto', 'department' => 'University Common Units'],
        ];
    }

    private function teachingAssignments(): array
    {
        return [
            ['unit_code' => 'ICS 2102', 'lecturer_id' => 'COHES-LEC001', 'course' => 'BPH'],
            ['unit_code' => 'ICM 2111', 'lecturer_id' => 'COHES-LEC012', 'course' => 'BCH'],
            ['unit_code' => 'HBC 2103', 'lecturer_id' => 'HRD-LEC002', 'course' => 'BCOM'],
            ['unit_code' => 'BIT 2119', 'lecturer_id' => 'HRD-LEC15', 'course' => 'BSCM'],
            ['unit_code' => 'AFP 2101', 'lecturer_id' => 'COHES-LEC012', 'course' => 'MFSN'],
            ['unit_code' => 'ICS 2174', 'lecturer_id' => 'CET-LEC008', 'course' => 'BCE'],
            ['unit_code' => 'ICS 2101', 'lecturer_id' => 'CET-LEC008', 'course' => 'BCS'],
            ['unit_code' => 'BIT 2305', 'lecturer_id' => 'SCIT-LEC004', 'course' => 'BIT'],
            ['unit_code' => 'SMA 2104', 'lecturer_id' => 'HRD-LEC15', 'course' => 'BBC'],
        ];
    }

    private function students(): array
    {
        return [
            ['name' => 'Michael Mwangi', 'reg_number' => 'CHS222-0520/2022', 'course' => 'BCH'],
            ['name' => 'Josephine Maina', 'reg_number' => 'HRD222-0650/2023', 'course' => 'BCOM'],
            ['name' => 'Peter Wafula', 'reg_number' => 'CAS222-0156/2022', 'course' => 'MFSN'],
            ['name' => 'Joyce Wanjala', 'reg_number' => 'CET222-0102/2022', 'course' => 'BAT'],
            ['name' => 'Brian Otieno', 'reg_number' => 'BIT222-0056/2023', 'course' => 'BIT'],
            ['name' => 'Mary Mwangi', 'reg_number' => 'CHS222-0250/2022', 'course' => 'BCH'],
            ['name' => 'Abel Barasa', 'reg_number' => 'HRD222-0108/2023', 'course' => 'BCH'],
            ['name' => 'Linda Chebet', 'reg_number' => 'CAS222-0117/2022', 'course' => 'MFSN'],
            ['name' => 'Godwill Omondi', 'reg_number' => 'SCT222-0163/2022', 'course' => 'BBC'],
            ['name' => 'Tiffany Atieno', 'reg_number' => 'CET222-0250/2022', 'course' => 'BCE'],
        ];
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [
            $parts[0] ?? $name,
            $parts[1] ?? '',
        ];
    }

    private function studyYearFromRegistration(string $registrationNumber): int
    {
        if (str_ends_with($registrationNumber, '/2022')) {
            return 4;
        }

        if (str_ends_with($registrationNumber, '/2023')) {
            return 3;
        }

        return 1;
    }
}
