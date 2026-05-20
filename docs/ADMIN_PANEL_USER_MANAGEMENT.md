# Admin Panel User Management

This document describes the user management capabilities in the Liberu Social Network admin panel.

## Overview

The admin panel provides comprehensive user management features built with Filament, including role-based access control (RBAC) using Spatie Laravel Permission and Filament Shield.

## Features

### User Management

The User Resource (`/admin/users`) provides the following functionality:

#### User Listing
- View all users in the system
- Search by name or email
- Filter by email verification status
- Filter by assigned roles
- Sort by any column
- Paginated results

#### User Creation
- Create new users with name, email, and password
- Assign one or more roles during creation
- Set email verification status
- Mark account as active/inactive

#### User Editing
- Update user details (name, email)
- Change password (optional - leave blank to keep current)
- Modify role assignments
- Update email verification status
- Toggle account active status

#### Bulk Actions
- Delete multiple users at once

### Role & Permission Management

The system includes built-in role management via Filament Shield:

#### Default Roles
- **super_admin**: Full system access, unrestricted permissions
- **admin**: Standard administrative access
- **panel_user**: Basic panel access without specific permissions
- **free**: Free tier user with limited permissions

#### Permissions
Permissions follow the format `Action::Resource`, for example:
- `ViewAny::User` - Can view user list
- `View::User` - Can view individual user
- `Create::User` - Can create new users
- `Update::User` - Can edit users
- `Delete::User` - Can delete users
- And more...

### Access Control

User access is controlled through:

1. **UserPolicy** - Defines authorization logic for user management
2. **Role Assignment** - Users can have multiple roles
3. **Permission Checks** - Each action checks for appropriate permissions

## Usage

### Creating an Admin User

When you run the database seeder, a default super admin user is created:
- **Email**: admin@example.com
- **Password**: password
- **Role**: super_admin

```bash
php artisan migrate:fresh --seed
```

### Managing Users

1. Navigate to `/admin/users` in your admin panel
2. Use the "Create" button to add new users
3. Click on any user row to edit
4. Use the role dropdown to assign/remove roles
5. Toggle email verification and active status as needed

### Managing Roles

1. Navigate to `/admin/shield/roles` in your admin panel
2. Create new roles or edit existing ones
3. Assign permissions to roles
4. Roles are automatically synced with user permissions

## Database Structure

### Tables
- `users` - User accounts
- `roles` - Available roles
- `permissions` - Available permissions
- `model_has_roles` - User-to-role assignments
- `model_has_permissions` - Direct user permissions (optional)
- `role_has_permissions` - Role-to-permission assignments

## Security

### Best Practices
1. Always assign appropriate roles to users
2. Use the super_admin role sparingly
3. Regularly audit user permissions
4. Enable email verification for sensitive accounts
5. Deactivate accounts instead of deleting when possible

### Password Security
- Passwords are hashed using Laravel's Hash facade
- Minimum password requirements should be enforced (configure in Fortify)
- Password changes can be forced via the edit form

## API

### Seeders
- `ShieldSeeder` - Creates all permission entries
- `RolesSeeder` - Creates default roles and syncs permissions
- `DatabaseSeeder` - Orchestrates seeding and creates default admin

### Policies
- `UserPolicy` - Authorization for user management operations

### Resources
- `UserResource` - Main Filament resource for user CRUD
- `UserResource\Pages\ListUsers` - User listing page
- `UserResource\Pages\CreateUser` - User creation page
- `UserResource\Pages\EditUser` - User editing page

## Customization

### Adding New Permissions

Edit `database/seeders/ShieldSeeder.php` and add your permissions:

```php
$permissions = [
    'ViewAny::CustomResource',
    'Create::CustomResource',
    // ... more permissions
];
```

Then run:
```bash
php artisan db:seed --class=ShieldSeeder
```

### Creating New Roles

Use the admin panel at `/admin/shield/roles` or update `RolesSeeder.php`:

```php
$customRole = Role::firstOrCreate(['name' => 'custom_role']);
$customRole->givePermissionTo(['ViewAny::User', 'View::User']);
```

## Troubleshooting

### Permission Denied Errors
- Ensure the user has the required role assigned
- Check that the role has the necessary permissions
- Verify UserPolicy is registered in AuthServiceProvider

### Roles Not Appearing
- Run `php artisan cache:clear`
- Run `php artisan config:clear`
- Re-run seeders: `php artisan db:seed --class=RolesSeeder`

### Shield Resources Not Visible
- Ensure FilamentShieldPlugin is registered in AdminPanelProvider
- Check that user has appropriate permissions
- Clear browser cache

## References

- [Filament Documentation](https://filamentphp.com)
- [Filament Shield Documentation](https://github.com/bezhanSalleh/filament-shield)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
- [Laravel Authorization](https://laravel.com/docs/authorization)
