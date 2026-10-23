<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->dateTime('two_factor_confirmed_at', 6)->nullable()->after('two_factor_recovery_codes');
        });
        Schema::create('passkeys', function (Blueprint $table): void {
            $table->id();
            $table->uuid('user_id')->index();
            $table->string('name');
            $table->string('credential_id')->unique();
            $table->json('credential');
            $table->dateTime('last_used_at', 6)->nullable();
            $table->dateTime('created_at', 6)->nullable();
            $table->dateTime('updated_at', 6)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passkeys');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
    }
};
