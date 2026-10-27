<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_drafts', function (Blueprint $table): void {
            $table->timestamp('undone_at')->nullable();
            $table->unsignedBigInteger('undone_by')->nullable();
            // A cancelled completion remains recorded; finishing again creates a new snapshot.
            $table->dropUnique(['manuscript_id', 'number']);
            $table->index(['manuscript_id', 'undone_at', 'number']);
        });
    }

    public function down(): void
    {
        // Undo records and repeated draft numbers must survive code rollback.
    }
};
