<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poem_analysis_conversations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('poem_id');
            $table->unsignedBigInteger('author_id');
            $table->string('title');
            $table->unsignedBigInteger('connection_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['poem_id', 'author_id', 'deleted_at', 'updated_at'], 'poem_analysis_browse');
        });
        Schema::create('poem_analysis_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('poem_id');
            $table->string('fingerprint', 64);
            $table->string('title');
            $table->longText('content_md');
            $table->unsignedBigInteger('collection_id')->nullable();
            $table->string('collection_title')->nullable();
            $table->longText('collection_description')->nullable();
            $table->timestamps();
            $table->unique(['poem_id', 'fingerprint'], 'poem_analysis_snapshot_unique');
        });
        Schema::create('poem_author_notes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('poem_id')->index();
            $table->text('content');
            $table->unsignedBigInteger('source_message_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        foreach (['studio_assistant_messages', 'studio_assistant_turns'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->unsignedBigInteger('exercise_id')->nullable()->change();
                $table->unsignedBigInteger('poem_conversation_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        // Retain private discussion and immutable source history.
    }
};
