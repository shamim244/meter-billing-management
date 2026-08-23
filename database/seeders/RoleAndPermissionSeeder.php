<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Create a default admin user if not exists
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@nbpdcl-saas.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('AdminPass123!'),
                'phone' => '1234567890',
                'status' => 'active',
            ]
        );
        $adminUser->assignRole($adminRole);

        // Create a default test client user if not exists
        $testUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'phone' => '9876543210',
                'status' => 'active',
            ]
        );
        $testUser->assignRole($userRole);
    }
}
