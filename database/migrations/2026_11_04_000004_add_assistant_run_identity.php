<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_assistant_turns', function (Blueprint $table): void {
            $table->uuid('run_uuid')->nullable();
        });
    }

    public function down(): void
    {
        // Retain execution identity alongside saved reading history.
    }
};
