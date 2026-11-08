<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_assistant_connections', function (Blueprint $table): void {
            $table->unsignedInteger('daily_request_limit')->default(200);
            $table->unsignedInteger('turn_request_limit')->default(100);
            $table->decimal('input_rate_usd', 12, 6)->nullable();
            $table->decimal('output_rate_usd', 12, 6)->nullable();
            $table->json('allowance')->nullable();
            $table->timestamp('allowance_checked_at')->nullable();
        });
        Schema::table('studio_sandbox_ideas', fn (Blueprint $table) => $table->json('provenance')->nullable());
        Schema::create('studio_assistant_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('connection_id');
            $table->unsignedBigInteger('turn_id')->index();
            $table->uuid('run_uuid')->nullable();
            $table->string('provider', 40);
            $table->string('model');
            $table->string('purpose', 30);
            $table->string('status', 20)->default('running');
            $table->unsignedBigInteger('input_tokens')->nullable();
            $table->unsignedBigInteger('output_tokens')->nullable();
            $table->unsignedBigInteger('cache_read_tokens')->nullable();
            $table->unsignedBigInteger('cache_write_tokens')->nullable();
            $table->json('pricing')->nullable();
            $table->decimal('estimated_cost_usd', 16, 8)->nullable();
            $table->decimal('reported_cost_usd', 16, 8)->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['connection_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_assistant_requests');
        Schema::table('studio_sandbox_ideas', fn (Blueprint $table) => $table->dropColumn('provenance'));
        Schema::table('studio_assistant_connections', function (Blueprint $table): void {
            $table->dropColumn(['daily_request_limit', 'turn_request_limit', 'allowance', 'allowance_checked_at', 'input_rate_usd', 'output_rate_usd']);
        });
    }
};
