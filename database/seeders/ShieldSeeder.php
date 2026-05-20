<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

class ShieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generate Shield permissions for all resources, pages, and widgets
        // This will create permissions like ViewAny::User, View::User, Create::User, etc.
        
        $this->command->info('Generating Shield permissions...');
        
        // Create basic user management permissions
        $permissions = [
            'ViewAny::User',
            'View::User',
            'Create::User',
            'Update::User',
            'Delete::User',
            'DeleteAny::User',
            'Restore::User',
            'RestoreAny::User',
            'ForceDelete::User',
            'ForceDeleteAny::User',
            'Replicate::User',
            'Reorder::User',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $this->command->info('Shield permissions generated successfully!');
    }
}
