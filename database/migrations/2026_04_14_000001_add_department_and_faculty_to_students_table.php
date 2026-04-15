<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        $hasDepartment = Schema::hasColumn('students', 'department');
        $hasFaculty = Schema::hasColumn('students', 'faculty');

        if ($hasDepartment && $hasFaculty) {
            return;
        }

        Schema::table('students', function (Blueprint $table) use ($hasDepartment, $hasFaculty) {
            if (! $hasDepartment) {
                $table->string('department')->nullable()->after('year');
            }

            if (! $hasFaculty) {
                $table->string('faculty')->nullable()->after($hasDepartment ? 'department' : 'year');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        $columnsToDrop = [];

        if (Schema::hasColumn('students', 'department')) {
            $columnsToDrop[] = 'department';
        }

        if (Schema::hasColumn('students', 'faculty')) {
            $columnsToDrop[] = 'faculty';
        }

        if ($columnsToDrop === []) {
            return;
        }

        Schema::table('students', function (Blueprint $table) use ($columnsToDrop) {
            $table->dropColumn($columnsToDrop);
        });
    }
};
