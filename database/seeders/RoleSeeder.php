<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ Clear cached roles/permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ✅ Create roles (guard_name important if using sanctum/api)
        $roles = ['admin', 'manager', 'waiters'];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }
    }
}