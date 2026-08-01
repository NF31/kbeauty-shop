<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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

        // DatabaseSeeder utilise WithoutModelEvents, qui coupe l'event
        // `saved` que Spatie ecoute pour invalider son cache de permissions.
        // Sans ce flush explicite, syncPermissions() ci-dessous ne retrouve
        // pas les permissions qu'on vient de creer dans la meme requete.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions($permissions);

        $staff = Role::findOrCreate('staff');
        $staff->syncPermissions(['products.manage', 'orders.manage']);

        $support = Role::findOrCreate('support');
        $support->syncPermissions(['orders.manage', 'reviews.moderate']);
    }
}
