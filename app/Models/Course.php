<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Course extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code', 'name', 'faculty', 'faculty_code'];

    public function students()
    {
        return $this->hasMany(Student::class, 'course', 'code');
    }

    public function teachingAssignments()
    {
        return $this->hasMany(UnitLecturer::class, 'course', 'code');
    }

    public function getDisplayNameAttribute(): string
    {
        $name = trim((string) $this->name);
        $code = $this->normalizedCode();

        if ($name === '') {
            return $code;
        }

        return $code !== '' ? "{$name} ({$code})" : $name;
    }

    public function getFacultyLabelAttribute(): string
    {
        $faculty = trim((string) $this->faculty);
        $facultyCode = trim((string) $this->faculty_code);

        if ($faculty === '') {
            return $facultyCode;
        }

        return $facultyCode !== '' ? "{$faculty} ({$facultyCode})" : $faculty;
    }

    public static function normalizeCode(?string $course): string
    {
        return Str::upper(trim((string) $course));
    }

    private function normalizedCode(): string
    {
        return self::normalizeCode($this->getKey());
    }
}
