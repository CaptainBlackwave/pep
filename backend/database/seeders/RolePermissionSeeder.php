<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // All permissions use the sanctum guard (API-only backend)
        $permissions = [
            // User Management
            'manage users',
            'view users',
            'create users',
            'edit users',
            'delete users',
            'ban users',

            // Module Management
            'manage modules',
            'toggle modules',

            // Game Management
            'manage crimes',
            'manage gangs',
            'manage properties',
            'manage locations',
            'manage items',
            'manage drugs',
            'manage ranks',
            'manage organized crimes',
            'manage combat',
            'manage races',
            'manage stocks',
            'manage casino',

            // Forum Management
            'manage forum',
            'moderate forum',
            'lock topics',
            'delete posts',

            // Chat Management
            'moderate chat',
            'manage chat channels',

            // Support
            'manage tickets',
            'view reports',

            // Community Content
            'community-content.create',
            'community-content.update',
            'community-content.publish',
            'community-content.manage',

            // Email Management
            'manage email settings',
            'manage email templates',
            'send emails',

            // System
            'view admin panel',
            'view logs',
            'manage settings',
            'manage roles',
            'manage permissions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'sanctum',
            ]);
        }

        // Create roles with sanctum guard
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $moderator = Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'sanctum']);
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'sanctum']);
        $contentEditor = Role::firstOrCreate(['name' => 'content-editor', 'guard_name' => 'sanctum']);
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);

        // Admin gets ALL permissions
        $admin->syncPermissions(Permission::where('guard_name', 'sanctum')->get());

        // Moderator gets limited permissions
        $moderator->syncPermissions([
            'view admin panel',
            'view users',
            'ban users',
            'moderate forum',
            'lock topics',
            'delete posts',
            'moderate chat',
            'manage tickets',
            'view reports',
            'community-content.create',
            'community-content.update',
            'community-content.publish',
        ]);

        // Staff can write frontend community content drafts without admin panel access.
        $staff->syncPermissions([
            'community-content.create',
            'community-content.update',
        ]);

        // Content editors can review and publish community content without full admin access.
        $contentEditor->syncPermissions([
            'community-content.create',
            'community-content.update',
            'community-content.publish',
            'community-content.manage',
        ]);

        // User role gets no admin permissions (they just play the game)
        $user->syncPermissions([]);

        $this->command->info('Roles and permissions created successfully (sanctum guard)!');
        $this->command->info('- admin: Full access');
        $this->command->info('- moderator: Forum/chat moderation, user bans, tickets');
        $this->command->info('- staff: Frontend content draft access');
        $this->command->info('- content-editor: Frontend content publishing access');
        $this->command->info('- user: Regular player (no admin access)');
    }
}
