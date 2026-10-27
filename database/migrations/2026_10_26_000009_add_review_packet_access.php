<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_compiles', function (Blueprint $table): void {
            $table->string('assistant_token_hash', 64)->nullable();
            $table->timestamp('assistant_revoked_at')->nullable();
        });
    }

    public function down(): void
    {
        // Retain access revocation history alongside the private packet.
    }
};
