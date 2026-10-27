<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_worlds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('author_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['author_id', 'updated_at']);
        });
        Schema::create('studio_magic_decisions', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
        });
        foreach (['exploring' => 'Exploring', 'settled' => 'Settled', 'discarded' => 'Discarded'] as $key => $name) {
            DB::table('studio_magic_decisions')->insert(['key' => $key, 'name' => $name]);
        }
        Schema::create('studio_magic_variation_modes', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
        });
        foreach (['qualify' => 'Qualification', 'replace' => 'Alternate rule', 'exclude' => 'Not applicable'] as $key => $name) {
            DB::table('studio_magic_variation_modes')->insert(['key' => $key, 'name' => $name]);
        }
        Schema::create('studio_magic_systems', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('world_id');
            $table->string('name');
            $table->longText('notes_md');
            $table->json('aliases');
            $table->unsignedBigInteger('revision')->default(1);
            $table->timestamps();
            $table->index(['world_id', 'name']);
        });
        Schema::create('studio_magic_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('system_id');
            $table->unsignedBigInteger('decision_id');
            $table->string('title');
            foreach (['statement_md', 'conditions_md', 'consequences_md', 'exceptions_md', 'reasoning_md'] as $column) {
                $table->text($column);
            }
            $table->unsignedBigInteger('revision')->default(1);
            $table->timestamps();
            $table->index(['system_id', 'decision_id']);
        });
        Schema::create('studio_magic_cases', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('system_id');
            $table->string('title');
            $table->text('situation_md');
            $table->text('outcome_md');
            $table->text('reasoning_md');
            $table->timestamps();
            $table->index(['system_id', 'updated_at']);
        });
        Schema::create('studio_magic_case_rules', function (Blueprint $table): void {
            $table->unsignedBigInteger('case_id');
            $table->unsignedBigInteger('rule_id');
            $table->unique(['case_id', 'rule_id']);
            $table->index('rule_id');
        });
        Schema::create('studio_magic_variations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('system_id');
            $table->unsignedBigInteger('manuscript_id');
            $table->unsignedBigInteger('rule_id');
            $table->unsignedBigInteger('mode_id');
            $table->text('text_md');
            $table->text('reason_md');
            $table->unsignedBigInteger('source_revision');
            $table->json('source_rule');
            $table->unsignedBigInteger('revision')->default(1);
            $table->timestamps();
            $table->unique(['manuscript_id', 'rule_id']);
            $table->index(['system_id', 'manuscript_id']);
        });
        Schema::create('studio_magic_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('system_id');
            $table->unsignedBigInteger('revision');
            $table->unsignedBigInteger('created_by');
            $table->json('snapshot');
            $table->timestamp('created_at');
            $table->unique(['system_id', 'revision']);
        });
        Schema::table('studio_manuscripts', fn (Blueprint $table) => $table->unsignedBigInteger('world_id')->nullable()->index());
        Schema::table('studio_drafts', fn (Blueprint $table) => $table->json('world_context')->nullable());
        foreach (['view', 'create', 'update'] as $ability) {
            $permission = Permission::findOrCreate('worlds.'.$ability, 'web');
            foreach (Role::where('guard_name', 'web')->whereIn('name', ['author', 'admin', 'superadmin'])->get() as $role) {
                $role->givePermissionTo($permission);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Retain authored worlds and captured draft context on code rollback.
    }
};
