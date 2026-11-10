<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_exchange_imports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('author_id');
            $table->uuid('package_id');
            $table->uuid('parent_package_id')->nullable();
            $table->string('destination_type', 30);
            $table->unsignedBigInteger('world_id')->nullable();
            $table->unsignedBigInteger('exercise_id')->nullable();
            $table->unsignedBigInteger('poem_id')->nullable();
            $table->unsignedBigInteger('poem_conversation_id')->nullable();
            $table->unsignedBigInteger('snapshot_id')->nullable();
            $table->unsignedSmallInteger('format_version');
            $table->string('title');
            $table->string('status', 20)->default('previewed');
            $table->string('raw_path');
            $table->char('raw_hash', 64);
            $table->char('canonical_hash', 64);
            $table->longText('body_md');
            $table->json('payload');
            $table->json('receipt')->nullable();
            $table->json('external_origin')->nullable();
            $table->timestamp('saved_at')->nullable();
            $table->timestamps();
            $table->unique(['author_id', 'package_id'], 'exchange_import_package_unique');
            $table->index('exercise_id');
            $table->index('poem_conversation_id');
            $table->index(['author_id', 'destination_type', 'status'], 'exchange_import_browse');
            $table->index(['status', 'created_at'], 'exchange_import_prune');
        });
        Schema::table('studio_assistant_messages', function (Blueprint $table): void {
            $table->unsignedBigInteger('import_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        // Retain imported work and its private source files.
    }
};
