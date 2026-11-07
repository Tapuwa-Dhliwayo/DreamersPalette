<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_magic_cases', function (Blueprint $table): void {
            $table->unsignedInteger('revision')->default(1);
        });
    }

    public function down(): void
    {
        // Retain authored case revisions on code rollback.
    }
};
