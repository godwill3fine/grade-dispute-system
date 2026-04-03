<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        DB::table('roles')->insertOrIgnore([
            ['name' => 'student', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'lecturer', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'hod', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'exam_officer', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'admin', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
