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
        // Get all available permissions
        $allPermissions = Permission::where('guard_name', 'web')->pluck('id')->toArray();

        // Create super_admin role (as defined in Shield config) with all permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdminRole->syncPermissions($allPermissions);

        // Create admin role with all permissions (can be customized later)
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions($allPermissions);

        // Create panel_user role (as defined in Shield config) - basic panel access
        $panelUserRole = Role::firstOrCreate(['name' => 'panel_user']);
        // Panel users get view-only permissions by default
        $viewPermissions = Permission::where('guard_name', 'web')
            ->where('name', 'like', 'ViewAny::%')
            ->orWhere('name', 'like', 'View::%')
            ->pluck('id')
            ->toArray();
        $panelUserRole->syncPermissions($viewPermissions);
        
        // Create free role - limited permissions for free tier users
        $freeRole = Role::firstOrCreate(['name' => 'free']);
        // Free users only get basic view permissions, no user management
        $freePermissions = Permission::where('guard_name', 'web')
            ->where(function ($query) {
                $query->where('name', 'like', 'ViewAny::%')
                      ->orWhere('name', 'like', 'View::%');
            })
            ->where('name', 'not like', '%User%') // Exclude user management
            ->pluck('id')
            ->toArray();
        $freeRole->syncPermissions($freePermissions);
    }
}
