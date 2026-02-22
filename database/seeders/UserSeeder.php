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

        // ==============================
        // 1) System Admin
        // ==============================
        $admin = User::updateOrCreate(
            ['email' => 'admin@ashbhub.com'],
            [
                'name'     => 'System Admin',
                'password' => Hash::make('Admin@0123'),
                'role'     => 'admin', // ✅ column in users table
            ]
        );

        // ✅ Assign Spatie role
        if (method_exists($admin, 'syncRoles')) {
            $admin->syncRoles(['admin']);
        }

        // ==============================
        // 2) Royal Crown (Manager)
        // ==============================
        $royalCrown = User::updateOrCreate(
            ['email' => 'admin@royalcrown.rw'],
            [
                'name'     => 'Royal Crown',
                'password' => Hash::make('Admin@123'),
                'role'     => 'manager',
            ]
        );

        if (method_exists($royalCrown, 'syncRoles')) {
            $royalCrown->syncRoles(['manager']);
        }

        // ==============================
        // 3) Olympic Hotel (Manager)
        // ==============================
        $olympicHotel = User::updateOrCreate(
            ['email' => 'admin@olympichotel.rw'],
            [
                'name'     => 'Olympic Hotel',
                'password' => Hash::make('Admin@123'),
                'role'     => 'manager',
            ]
        );

        if (method_exists($olympicHotel, 'syncRoles')) {
            $olympicHotel->syncRoles(['manager']);
        }
    }
}