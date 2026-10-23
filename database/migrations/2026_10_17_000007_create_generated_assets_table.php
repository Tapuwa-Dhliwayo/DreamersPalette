<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_assets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('source_uuid')->nullable()->unique();
            $table->unsignedBigInteger('author_id');
            $table->string('type');
            $table->text('prompt');
            $table->text('image_url');
            $table->dateTime('created_at', 6)->nullable();
            $table->index(['author_id', 'created_at'], 'assets_author_listing_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_assets');
    }
};
