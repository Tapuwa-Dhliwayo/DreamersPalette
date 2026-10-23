<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poems', function (Blueprint $table): void {
            $table->id();
            $table->uuid('source_uuid')->nullable()->unique();
            $table->unsignedBigInteger('author_id');
            $table->unsignedBigInteger('collection_id');
            $table->string('title');
            $table->string('slug')->collation('utf8mb4_bin')->unique();
            $table->longText('content_md');
            $table->text('excerpt')->nullable();
            $table->boolean('is_published')->default(false);
            $table->dateTime('published_at', 6)->nullable();
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();
            $table->dateTime('deleted_at', 6)->nullable();
            $table->index(['is_published', 'deleted_at', 'created_at'], 'poems_public_listing_index');
            $table->index(['collection_id', 'is_published', 'deleted_at', 'created_at'], 'poems_collection_listing_index');
            $table->index(['author_id', 'deleted_at', 'created_at'], 'poems_author_listing_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poems');
    }
};
