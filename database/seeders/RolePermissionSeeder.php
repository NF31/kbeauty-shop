<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the roles and permissions defined in docs/DATA_MODEL.md.
     */
    public function run(): void
    {
        $permissions = [
            'products.manage',
            'orders.manage',
            'orders.refund',
            'reviews.moderate',
            'content.manage',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions($permissions);

        $staff = Role::findOrCreate('staff');
        $staff->syncPermissions(['products.manage', 'orders.manage']);

        $support = Role::findOrCreate('support');
        $support->syncPermissions(['orders.manage', 'reviews.moderate']);
    }
}
