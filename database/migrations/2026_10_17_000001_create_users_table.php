<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            $columns = ['id', 'name', 'email', 'password', 'email_verified_at', 'remember_token', 'created_at', 'updated_at'];
            if (Schema::getColumnType('users', 'id') !== 'bigint' || array_diff($columns, Schema::getColumnListing('users'))) {
                throw new RuntimeException('Publishing requires the shared Laravel users table with numeric IDs. No user records were changed.');
            }

            return;
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // The family user table may already belong to the blog; publishing never drops it.
    }
};
