<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'manage users',
            'manage clients',
            'manage projects',
            'manage payments',
            'manage expenses',
            'view dashboard',
            'generate invoices',
            'send emails',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $developerRole = Role::firstOrCreate(['name' => 'Developer']);
        $socialMediaRole = Role::firstOrCreate(['name' => 'Social Media Manager']);

        // Assign permissions to roles
        $adminRole->givePermissionTo($permissions);

        $developerRole->givePermissionTo([
            'manage clients',
            'manage projects',
            'manage payments',
            'view dashboard',
            'generate invoices',
        ]);

        $socialMediaRole->givePermissionTo([
            'manage clients',
            'manage projects',
            'view dashboard',
        ]);
    }
}
