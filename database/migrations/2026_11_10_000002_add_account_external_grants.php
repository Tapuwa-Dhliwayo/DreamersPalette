<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        // Default deny: a connected AI client can read only the rows an author lists here.
        Schema::create('account_external_grants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('resource_type', 30);
            $table->unsignedBigInteger('resource_id');
            $table->boolean('includes_workspace')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'resource_type', 'resource_id']);
            $table->index('user_id');
        });

        $permission = Permission::findOrCreate('account.external-access', 'web');
        $accountViewers = Permission::where('name', 'account.view')->where('guard_name', 'web')->first();
        foreach ($accountViewers?->roles ?? [] as $role) {
            $role->givePermissionTo($permission);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Grants are author consent records; they are never dropped by a rollback.
    }
};
