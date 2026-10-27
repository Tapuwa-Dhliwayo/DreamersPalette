<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_manuscripts', fn (Blueprint $table) => $table->unsignedBigInteger('book_id')->nullable()->unique());
        Schema::table('studio_chapters', fn (Blueprint $table) => $table->unsignedBigInteger('public_chapter_id')->nullable()->unique());
        Schema::table('chapters', fn (Blueprint $table) => $table->unsignedInteger('reading_position')->nullable()->index());
        Schema::create('studio_releases', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('book_id');
            $table->unsignedBigInteger('manuscript_id');
            $table->unsignedBigInteger('draft_id')->nullable();
            $table->unsignedInteger('number');
            $table->uuid('request_uuid')->unique();
            $table->json('snapshot');
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at');
            $table->unique(['book_id', 'number']);
        });
    }

    public function down(): void {}
};
