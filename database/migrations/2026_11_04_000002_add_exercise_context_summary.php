<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_world_exercises', function (Blueprint $table): void {
            $table->unsignedBigInteger('summary_through_id')->nullable();
        });
    }

    public function down(): void
    {
        // Keep the link from retained summaries to their source conversation.
    }
};
