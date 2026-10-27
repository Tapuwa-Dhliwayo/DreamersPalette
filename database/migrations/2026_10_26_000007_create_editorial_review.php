<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_review_grants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manuscript_id')->index();
            $table->unsignedBigInteger('draft_id');
            $table->unsignedBigInteger('reviewer_id');
            $table->unsignedBigInteger('created_by');
            $table->boolean('can_suggest')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['reviewer_id', 'revoked_at', 'expires_at'], 'studio_reviewer_access');
        });
        Schema::create('studio_review_comments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('grant_id')->index();
            $table->unsignedBigInteger('chapter_id');
            $table->unsignedBigInteger('created_by');
            $table->longText('body_md');
            $table->longText('suggested_md')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->unsignedBigInteger('accepted_by')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->uuid('request_uuid')->unique();
            $table->timestamps();
        });
        $editor = Role::findOrCreate('editor', 'web');
        foreach (['reviews.view', 'reviews.comment', 'reviews.suggest', 'dashboard.access', 'account.view', 'account.security.manage'] as $ability) {
            $editor->givePermissionTo(Permission::findOrCreate($ability, 'web'));
        }
        foreach (['reviews.view', 'reviews.comment', 'reviews.suggest'] as $ability) {
            foreach (Role::where('guard_name', 'web')->whereIn('name', ['author', 'admin', 'superadmin'])->get() as $role) {
                $role->givePermissionTo(Permission::findOrCreate($ability, 'web'));
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void {}
};
