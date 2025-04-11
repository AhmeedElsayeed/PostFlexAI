<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Admin role
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Editor role
        $editor = Role::create(['name' => 'editor']);
        $editor->givePermissionTo([
            'view media',
            'create media',
            'edit media',
            'delete media',
            // ... other editor permissions ...
        ]);

        // Viewer role
        $viewer = Role::create(['name' => 'viewer']);
        $viewer->givePermissionTo([
            'view media',
            // ... other viewer permissions ...
        ]);

        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'marketer']);
        Role::create(['name' => 'client']);
    }
} 