<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('display_name');
            $table->text('bio')->nullable();
            $table->text('avatar_url')->nullable();
            // Preserve the source label; permissions will own application access.
            $table->string('role')->default('author');
            $table->dateTime('created_at', 6)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
