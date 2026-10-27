<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_world_people', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('world_id');
            $table->string('name');
            $table->json('aliases');
            $table->json('roles');
            $table->text('domain')->nullable();
            $table->longText('notes_md')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->index(['world_id', 'name']);
        });
        Schema::create('studio_world_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('world_id');
            $table->string('title');
            $table->string('date_label')->nullable();
            $table->decimal('position', 14, 3);
            $table->longText('notes_md')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->index(['world_id', 'position']);
        });
        Schema::create('studio_world_event_people', function (Blueprint $table): void {
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('person_id');
            $table->unique(['event_id', 'person_id']);
            $table->index('person_id');
        });
        Schema::create('studio_story_arcs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('world_id');
            $table->string('title');
            $table->unsignedBigInteger('person_id')->nullable()->index();
            $table->unsignedBigInteger('manuscript_id')->nullable()->index();
            $table->string('measure')->default('Pressure');
            $table->longText('notes_md')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->index(['world_id', 'title']);
        });
        Schema::create('studio_arc_beats', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('arc_id');
            $table->string('title');
            $table->unsignedBigInteger('event_id')->nullable()->index();
            $table->decimal('position', 14, 3);
            $table->unsignedTinyInteger('intensity');
            $table->longText('notes_md')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->index(['arc_id', 'position']);
        });
        Schema::create('studio_alignment_charts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('world_id');
            $table->string('title');
            $table->string('left_label');
            $table->string('right_label');
            $table->string('top_label');
            $table->string('bottom_label');
            $table->longText('notes_md')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->index(['world_id', 'title']);
        });
        Schema::create('studio_alignment_placements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('chart_id');
            $table->string('name');
            $table->unsignedBigInteger('person_id')->nullable()->index();
            $table->unsignedBigInteger('system_id')->nullable()->index();
            $table->smallInteger('x');
            $table->smallInteger('y');
            $table->longText('reason_md')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->index(['chart_id', 'name']);
        });
    }

    public function down(): void
    {
        // Private authored worldbuilding is not disposable migration state.
    }
};
