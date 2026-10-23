<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('author_id');
            $table->string('title');
            $table->string('slug')->collation('utf8mb4_bin')->unique();
            $table->text('synopsis')->nullable();
            $table->text('cover_image_url')->nullable();
            $table->text('ai_prompt')->nullable();
            $table->text('theme_background_url')->nullable();
            $table->decimal('theme_overlay_opacity', 4, 3)->nullable()->default(0.65);
            $table->string('theme_text_mode')->nullable()->default('light');
            $table->string('accent_color', 7)->nullable();
            $table->boolean('is_published')->default(false);
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();
            $table->dateTime('deleted_at', 6)->nullable();
            $table->index(['is_published', 'deleted_at', 'created_at'], 'books_public_listing_index');
            $table->index(['author_id', 'deleted_at', 'created_at'], 'books_author_listing_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
