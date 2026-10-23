<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publishing_profiles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('source_uuid')->nullable()->unique();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('display_name');
            $table->text('bio')->nullable();
            $table->text('avatar_url')->nullable();
            // Preserve the source label; permissions will own application access.
            $table->string('role')->default('author');
            $table->dateTime('created_at', 6)->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->dateTime('two_factor_confirmed_at', 6)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publishing_profiles');
    }
};
