<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_drafts', function (Blueprint $table): void {
            $table->unsignedBigInteger('restored_from_draft_id')->nullable()->index();
        });
    }

    public function down(): void {}
};
