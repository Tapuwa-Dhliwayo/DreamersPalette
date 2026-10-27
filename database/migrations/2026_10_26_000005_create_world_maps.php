<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_world_maps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('world_id');
            $table->string('title');
            $table->string('image_path')->nullable();
            $table->unsignedInteger('width_px')->default(1200);
            $table->unsignedInteger('height_px')->default(800);
            $table->decimal('width_units', 18, 4)->nullable();
            $table->string('unit', 40)->default('km');
            $table->json('pins');
            $table->json('journey');
            $table->longText('notes_md')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->index(['world_id', 'deleted_at']);
        });
        Schema::create('studio_map_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('world_map_id');
            $table->unsignedInteger('revision');
            $table->json('snapshot');
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at');
            $table->unique(['world_map_id', 'revision']);
        });
    }

    public function down(): void {}
};
