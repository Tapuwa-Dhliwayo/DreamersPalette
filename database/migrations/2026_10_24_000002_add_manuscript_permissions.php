<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['view', 'create', 'update', 'finish', 'export'] as $ability) {
            $permission = Permission::findOrCreate('manuscripts.'.$ability, 'web');
            foreach (Role::where('guard_name', 'web')->whereIn('name', ['author', 'admin', 'superadmin'])->get() as $role) {
                $role->givePermissionTo($permission);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permissions may have been deliberately granted since deployment.
    }
};
