<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->splitNameColumn('students', 'reg_number');
        $this->splitNameColumn('lecturers', 'lecturer_id');
        $this->splitNameColumn('staff', 'staff_id');
    }

    public function down(): void
    {
        $this->combineNameColumns('students', 'reg_number');
        $this->combineNameColumns('lecturers', 'lecturer_id');
        $this->combineNameColumns('staff', 'staff_id');
    }

    private function splitNameColumn(string $tableName, string $keyColumn): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $hasFirstName = Schema::hasColumn($tableName, 'first_name');
        $hasLastName = Schema::hasColumn($tableName, 'last_name');

        if (! $hasFirstName || ! $hasLastName) {
            Schema::table($tableName, function (Blueprint $table) use ($hasFirstName, $hasLastName) {
                if (! $hasFirstName) {
                    $table->string('first_name')->nullable();
                }

                if (! $hasLastName) {
                    $table->string('last_name')->nullable();
                }
            });
        }

        if (! Schema::hasColumn($tableName, 'full_name')) {
            return;
        }

        DB::table($tableName)
            ->select($keyColumn, 'full_name')
            ->orderBy($keyColumn)
            ->get()
            ->each(function (object $row) use ($tableName, $keyColumn): void {
                [$firstName, $lastName] = $this->splitNameParts($row->full_name ?? null);

                DB::table($tableName)
                    ->where($keyColumn, $row->{$keyColumn})
                    ->update([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                    ]);
            });

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn('full_name');
        });
    }

    private function combineNameColumns(string $tableName, string $keyColumn): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $hasFullName = Schema::hasColumn($tableName, 'full_name');
        $hasFirstName = Schema::hasColumn($tableName, 'first_name');
        $hasLastName = Schema::hasColumn($tableName, 'last_name');

        if (! $hasFirstName && ! $hasLastName) {
            return;
        }

        if (! $hasFullName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('full_name')->nullable();
            });
        }

        DB::table($tableName)
            ->select($keyColumn, 'first_name', 'last_name')
            ->orderBy($keyColumn)
            ->get()
            ->each(function (object $row) use ($tableName, $keyColumn): void {
                DB::table($tableName)
                    ->where($keyColumn, $row->{$keyColumn})
                    ->update([
                        'full_name' => $this->combineNameParts(
                            $row->first_name ?? null,
                            $row->last_name ?? null
                        ),
                    ]);
            });

        Schema::table($tableName, function (Blueprint $table) use ($hasFirstName, $hasLastName) {
            $columnsToDrop = [];

            if ($hasFirstName) {
                $columnsToDrop[] = 'first_name';
            }

            if ($hasLastName) {
                $columnsToDrop[] = 'last_name';
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    private function splitNameParts(?string $fullName): array
    {
        $normalized = $this->normalizeName($fullName);
        if ($normalized === '') {
            return [null, null];
        }

        $parts = explode(' ', $normalized, 2);

        return [
            $parts[0],
            $parts[1] ?? null,
        ];
    }

    private function combineNameParts(?string $firstName, ?string $lastName): ?string
    {
        $parts = array_filter([
            $this->normalizeName($firstName),
            $this->normalizeName($lastName),
        ], static fn (?string $part): bool => $part !== null && $part !== '');

        $fullName = trim(implode(' ', $parts));

        return $fullName !== '' ? $fullName : null;
    }

    private function normalizeName(?string $value): string
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return '';
        }

        return (string) preg_replace('/\s+/', ' ', $trimmed);
    }
};
