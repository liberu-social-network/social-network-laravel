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
        $viewPermissions = $this->getViewOnlyPermissions();
        $panelUserRole->syncPermissions($viewPermissions);
        
        // Create free role - limited permissions for free tier users
        $freeRole = Role::firstOrCreate(['name' => 'free']);
        $freePermissions = $this->getViewOnlyPermissions(['%User%']);
        $freeRole->syncPermissions($freePermissions);
    }

    /**
     * Get view-only permissions, optionally excluding certain patterns.
     *
     * @param array $excludePatterns Patterns to exclude from the result
     * @return array
     */
    protected function getViewOnlyPermissions(array $excludePatterns = []): array
    {
        $query = Permission::where('guard_name', 'web')
            ->where(function ($q) {
                $q->where('name', 'like', 'ViewAny::%')
                  ->orWhere('name', 'like', 'View::%');
            });

        // Apply exclusion patterns if provided
        foreach ($excludePatterns as $pattern) {
            $query->where('name', 'not like', $pattern);
        }

        return $query->pluck('id')->toArray();
    }
}
