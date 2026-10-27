<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_world_relationships', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('world_id');
            $table->string('source_key', 40);
            $table->string('source_name');
            $table->string('target_key', 40);
            $table->string('target_name');
            $table->string('label');
            $table->string('period')->nullable();
            $table->longText('notes_md')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->index(['world_id', 'deleted_at']);
            $table->index(['world_id', 'source_key']);
            $table->index(['world_id', 'target_key']);
        });
        Schema::create('studio_world_comparisons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('world_id');
            $table->string('title');
            $table->json('criteria');
            $table->json('subjects');
            $table->longText('notes_md')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->index(['world_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        // Authored world material is retained.
    }
};
