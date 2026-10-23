<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table): void {
            $table->id();
            $table->uuid('source_uuid')->nullable()->unique();
            $table->unsignedBigInteger('author_id');
            $table->unsignedBigInteger('book_id');
            $table->integer('chapter_number');
            $table->string('title');
            $table->longText('content_md');
            $table->boolean('is_preview')->default(true);
            $table->boolean('is_published')->default(false);
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();
            $table->dateTime('deleted_at', 6)->nullable();
            $table->unique(['book_id', 'chapter_number']);
            $table->index(['book_id', 'is_published', 'deleted_at', 'chapter_number'], 'chapters_book_listing_index');
            $table->index(['author_id', 'deleted_at', 'created_at'], 'chapters_author_listing_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
