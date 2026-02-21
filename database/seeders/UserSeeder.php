<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ Make sure roles exist
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'manager']);
        Role::firstOrCreate(['name' => 'waiters']);

        $adminEmail = 'admin@ashbhub.com';
        $adminPassword = 'Admin@12345';

        $admin = User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'System Admin',
                'password' => Hash::make($adminPassword),

                // ✅ IMPORTANT: set role column here
                'role' => 'admin',
            ]
        );

        // ✅ Set Spatie role too (sync to avoid duplicates)
        if (method_exists($admin, 'syncRoles')) {
            $admin->syncRoles(['admin']);
        }
    }
}