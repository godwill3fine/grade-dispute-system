<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Staff;
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
            AcademicCatalogSeeder::class,
        ]);

        // User::factory(10)->create();

        $adminRoleId = Role::query()->where('name', 'admin')->value('id');

        Staff::query()->updateOrCreate(
            ['staff_id' => 'SYSAD-001'],
            [
                'first_name' => 'Martin',
                'last_name' => 'Waweru',
                'salutation' => null,
                'full_name' => 'Martin Waweru',
                'role' => 'admin',
                'department' => 'Administration',
            ]
        );

        User::query()->updateOrCreate(
            ['identifier' => 'SYSAD-001'],
            [
                'name' => 'Martin Waweru',
                'email' => 'sysad-001@grade-dispute.local',
                'role' => 'admin',
                'role_id' => $adminRoleId,
                'status' => 'active',
                'require_password_reset' => false,
                'manual_approval_required' => false,
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
