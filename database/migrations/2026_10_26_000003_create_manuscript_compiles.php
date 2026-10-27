<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_manuscripts', function (Blueprint $table): void {
            $table->json('compile_preferences')->nullable();
        });
        Schema::create('studio_compiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manuscript_id');
            $table->unsignedBigInteger('created_by');
            $table->uuid('request_uuid')->unique();
            $table->string('format', 12);
            $table->string('status', 20)->default('preparing');
            $table->json('manifest');
            $table->string('path')->nullable();
            $table->string('filename');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->index(['manuscript_id', 'created_at']);
        });
    }

    public function down(): void
    {
        // Keep private export manifests until their normal expiration.
    }
};
