<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_assistant_turns', function (Blueprint $table): void {
            $table->json('request_context')->nullable();
            $table->string('reading_depth', 20)->default('focused');
            $table->string('stage', 40)->nullable();
            $table->text('progress')->nullable();
        });
        Schema::create('studio_assistant_sources', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('turn_id')->index();
            $table->unsignedBigInteger('chapter_id')->index();
            $table->unsignedInteger('chapter_revision');
            $table->unsignedBigInteger('manuscript_id');
            $table->unsignedInteger('position');
            $table->string('title');
            $table->json('passages');
            $table->json('reads')->nullable();
            $table->timestamps();
            $table->unique(['turn_id', 'chapter_id']);
        });
    }

    public function down(): void
    {
        // Retain source evidence for saved questions and responses.
    }
};
