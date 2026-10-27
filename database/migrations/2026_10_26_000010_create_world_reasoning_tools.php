<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('studio_world_relationships', 'is_dependency')) {
            Schema::table('studio_world_relationships', function (Blueprint $table): void {
                $table->boolean('is_dependency')->default(false)->index();
            });
        }
        if (! Schema::hasTable('studio_world_investigations')) {
            Schema::create('studio_world_investigations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('world_id');
                $table->string('title');
                $table->json('subjects');
                $table->text('question_md');
                $table->json('evidence');
                $table->text('open_questions_md')->nullable();
                $table->text('conclusion_md')->nullable();
                $table->boolean('is_resolved')->default(false);
                $table->unsignedInteger('revision')->default(1);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('archived_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['world_id', 'deleted_at', 'is_resolved'], 'studio_investigation_browse_idx');
            });
        }
        if (! Schema::hasTable('studio_world_knowledge')) {
            Schema::create('studio_world_knowledge', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('world_id');
                $table->string('title');
                $table->text('truth_md')->nullable();
                $table->json('knowers');
                $table->unsignedBigInteger('manuscript_id')->nullable();
                $table->unsignedBigInteger('reveal_chapter_id')->nullable();
                $table->text('notes_md')->nullable();
                $table->unsignedInteger('revision')->default(1);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('archived_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['world_id', 'deleted_at']);
                $table->index(['manuscript_id', 'reveal_chapter_id']);
            });
        }
    }

    public function down(): void
    {
        // Preserve authored reasoning and knowledge records.
    }
};
