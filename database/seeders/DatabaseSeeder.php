<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesTableSeeder::class,
        ]);

        // User::factory(10)->create();

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'identifier' => 'ADMIN001',
                'role' => 'admin',
                'password' => Hash::make('password'),
            ]
        );

        SystemSetting::query()->updateOrCreate(
            ['setting_key' => 'escalation_days_hod'],
            ['setting_value' => '3', 'updated_by' => 'seed']
        );

        SystemSetting::query()->updateOrCreate(
            ['setting_key' => 'escalation_days_admin'],
            ['setting_value' => '6', 'updated_by' => 'seed']
        );
    }
}
