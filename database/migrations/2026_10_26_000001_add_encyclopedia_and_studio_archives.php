<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_entry_types', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
        });
        foreach (['location' => 'Location', 'species' => 'Species', 'culture' => 'Culture', 'faction' => 'Faction', 'item' => 'Item', 'religion' => 'Religion', 'philosophy' => 'Philosophy', 'language' => 'Language', 'resource' => 'Resource', 'concept' => 'Concept'] as $key => $name) {
            DB::table('studio_entry_types')->insert(['key' => $key, 'name' => $name]);
        }
        Schema::create('studio_world_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('world_id');
            $table->unsignedBigInteger('type_id')->index();
            $table->string('name');
            $table->json('aliases');
            $table->longText('notes_md')->nullable();
            $table->uuid('creation_uuid')->nullable()->unique();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->index(['world_id', 'name']);
            $table->index(['world_id', 'deleted_at', 'type_id']);
        });
        Schema::create('studio_chapter_references', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manuscript_id')->index();
            $table->unsignedBigInteger('chapter_id');
            $table->unsignedBigInteger('world_id')->index();
            $table->string('target_kind', 20);
            $table->unsignedBigInteger('target_id');
            $table->string('target_name');
            $table->text('wording');
            $table->text('prefix');
            $table->text('suffix');
            $table->char('anchor_hash', 64);
            $table->unsignedInteger('source_revision');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes()->index();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->unique(['chapter_id', 'target_kind', 'target_id', 'anchor_hash'], 'studio_reference_anchor');
            $table->index(['world_id', 'target_kind', 'target_id'], 'studio_reference_target');
        });
        foreach (['studio_worlds', 'studio_manuscripts', 'studio_magic_systems', 'studio_magic_rules', 'studio_magic_cases', 'studio_world_people', 'studio_world_events', 'studio_story_arcs', 'studio_arc_beats', 'studio_alignment_charts', 'studio_alignment_placements'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->softDeletes()->index();
                $table->unsignedBigInteger('archived_by')->nullable();
            });
        }
        Schema::table('studio_world_people', function (Blueprint $table): void {
            $table->uuid('creation_uuid')->nullable()->unique();
        });
        Schema::table('studio_chapters', function (Blueprint $table): void {
            $table->unsignedBigInteger('archived_by')->nullable();
        });
    }

    public function down(): void
    { /* Retain authored material and reference history. */
    }
};
