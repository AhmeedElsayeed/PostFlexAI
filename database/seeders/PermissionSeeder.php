<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Media Library Permissions
        Permission::create(['name' => 'view media']);
        Permission::create(['name' => 'create media']);
        Permission::create(['name' => 'edit media']);
        Permission::create(['name' => 'delete media']);
    }
} 