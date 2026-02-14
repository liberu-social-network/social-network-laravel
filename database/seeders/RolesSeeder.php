<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super_admin role (as defined in Shield config)
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $permissions = Permission::where('guard_name', 'web')->pluck('id')->toArray();
        $superAdminRole->syncPermissions($permissions);

        // Create admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions($permissions);

        // Create panel_user role (as defined in Shield config)
        $panelUserRole = Role::firstOrCreate(['name' => 'panel_user']);
        
        // Create free role
        $freeRole = Role::firstOrCreate(['name' => 'free']);
        $freePermissions = Permission::where('guard_name', 'web')->pluck('id')->toArray();
        $freeRole->syncPermissions($freePermissions);
    }
}
