<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_world_calendars', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('world_id');
            $table->string('name');
            $table->json('months');
            $table->json('weekdays');
            $table->unsignedInteger('leap_every')->nullable();
            $table->unsignedInteger('leap_month')->nullable();
            $table->unsignedInteger('leap_days')->default(1);
            $table->text('notes_md')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->index(['world_id', 'deleted_at']);
        });
        Schema::table('studio_world_events', function (Blueprint $table): void {
            $table->unsignedBigInteger('calendar_id')->nullable();
            $table->unsignedBigInteger('calendar_day')->nullable();
            $table->index(['world_id', 'calendar_id', 'calendar_day'], 'studio_calendar_chronology');
        });
    }

    public function down(): void {}
};
