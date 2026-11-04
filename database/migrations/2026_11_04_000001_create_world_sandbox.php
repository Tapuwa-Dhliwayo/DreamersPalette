<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_assistant_connections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name');
            $table->string('provider', 40);
            $table->string('model');
            $table->text('credential')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
        });
        Schema::create('studio_world_exercises', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('world_id')->index();
            $table->string('title');
            $table->text('purpose')->nullable();
            $table->text('instructions')->nullable();
            $table->longText('summary')->nullable();
            $table->json('context_keys')->nullable();
            $table->json('passages')->nullable();
            $table->unsignedBigInteger('connection_id')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['world_id', 'deleted_at', 'updated_at'], 'exercise_browse_idx');
        });
        Schema::create('studio_assistant_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('exercise_id')->index();
            $table->string('role', 20);
            $table->longText('content');
            $table->timestamps();
        });
        Schema::create('studio_assistant_turns', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('exercise_id')->index();
            $table->unsignedBigInteger('connection_id');
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('response_id')->nullable();
            $table->uuid('request_uuid')->unique();
            $table->string('provider', 40);
            $table->string('model');
            $table->string('mode', 25)->default('conversation');
            $table->string('status', 25)->default('queued');
            $table->json('context_manifest');
            $table->longText('prompt');
            $table->json('proposals')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['exercise_id', 'status'], 'turn_status_idx');
        });
        Schema::create('studio_sandbox_ideas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('exercise_id')->index();
            $table->string('kind', 30)->default('thought');
            $table->string('title');
            $table->longText('notes_md')->nullable();
            $table->string('uncertainty')->nullable();
            $table->string('alternative')->nullable();
            $table->string('lane')->nullable();
            $table->decimal('position', 15, 3)->default(0);
            $table->string('date_label')->nullable();
            $table->json('fields')->nullable();
            $table->string('target_key')->nullable();
            $table->unsignedInteger('target_revision')->nullable();
            $table->unsignedBigInteger('source_message_id')->nullable();
            $table->string('origin', 20)->default('author');
            $table->string('source_token')->nullable()->unique();
            $table->integer('x')->default(40);
            $table->integer('y')->default(40);
            $table->unsignedInteger('revision')->default(1);
            $table->timestamp('canonised_at')->nullable();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['exercise_id', 'deleted_at', 'position'], 'idea_browse_idx');
        });
        Schema::create('studio_sandbox_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('exercise_id')->index();
            $table->unsignedBigInteger('source_id')->index();
            $table->unsignedBigInteger('target_id')->index();
            $table->string('kind', 25)->default('connection');
            $table->string('label');
            $table->text('notes_md')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->unique(['exercise_id', 'source_id', 'target_id', 'kind', 'label'], 'sandbox_link_unique');
        });
        Schema::create('studio_naming_conventions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('world_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name');
            $table->longText('guidance');
            $table->text('examples')->nullable();
            $table->text('source')->nullable();
            $table->boolean('reviewed')->default(false);
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
        });
        Schema::create('studio_canon_changes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('exercise_id')->index();
            $table->unsignedBigInteger('user_id');
            $table->uuid('request_uuid')->unique();
            $table->json('changes');
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Authored exercises, conversations and accepted history must survive rollbacks.
    }
};
