<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_manuscripts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('author_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('working_draft_number')->default(1);
            $table->unsignedBigInteger('based_on_draft_id')->nullable();
            $table->unsignedBigInteger('revision')->default(1);
            $table->timestamps();
            $table->index(['author_id', 'updated_at']);
        });
        Schema::create('studio_chapters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manuscript_id');
            $table->unsignedInteger('position');
            $table->string('title');
            $table->longText('content_md');
            $table->text('summary_md')->nullable();
            $table->unsignedBigInteger('revision')->default(1);
            $table->unsignedBigInteger('updated_by');
            $table->uuid('last_save_uuid')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['manuscript_id', 'deleted_at', 'position']);
        });
        Schema::create('studio_drafts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manuscript_id');
            $table->unsignedInteger('number');
            $table->string('name', 160);
            $table->text('note')->nullable();
            $table->string('manuscript_title');
            $table->unsignedBigInteger('source_revision');
            $table->unsignedBigInteger('created_by');
            $table->uuid('request_uuid');
            $table->timestamp('created_at');
            $table->unique(['manuscript_id', 'number']);
            $table->unique(['manuscript_id', 'request_uuid']);
        });
        Schema::create('studio_draft_chapters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('draft_id');
            $table->unsignedBigInteger('source_chapter_id');
            $table->unsignedInteger('position');
            $table->string('title');
            $table->longText('content_md');
            $table->text('summary_md')->nullable();
            $table->unsignedBigInteger('source_revision');
            $table->unique(['draft_id', 'source_chapter_id']);
            $table->index(['draft_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_draft_chapters');
        Schema::dropIfExists('studio_drafts');
        Schema::dropIfExists('studio_chapters');
        Schema::dropIfExists('studio_manuscripts');
    }
};
