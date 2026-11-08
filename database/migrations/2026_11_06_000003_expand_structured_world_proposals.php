<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_world_people', function (Blueprint $table): void {
            $table->json('profile')->nullable();
        });
        Schema::table('studio_sandbox_links', function (Blueprint $table): void {
            $table->unsignedBigInteger('source_id')->nullable()->change();
            $table->unsignedBigInteger('target_id')->nullable()->change();
            $table->string('source_key', 80)->nullable()->index();
            $table->string('target_key', 80)->nullable()->index();
        });
        Schema::table('studio_arc_beats', function (Blueprint $table): void {
            $table->unsignedTinyInteger('intensity')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Existing writing may use these fields; rollback must not discard it.
        throw new RuntimeException('This additive writing migration requires a reviewed data-preserving reversal.');
    }
};
