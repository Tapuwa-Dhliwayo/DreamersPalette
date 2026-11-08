<?php

use App\Models\WritingActivity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_chapters', function (Blueprint $table): void {
            $table->unsignedInteger('word_count')->default(0);
        });
        Schema::table('studio_manuscripts', function (Blueprint $table): void {
            $table->unsignedInteger('weekly_word_goal')->nullable();
        });
        Schema::create('studio_writing_activity', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manuscript_id');
            $table->unsignedBigInteger('chapter_id')->nullable();
            $table->unsignedBigInteger('author_id');
            $table->string('kind', 20);
            $table->unsignedInteger('words_added')->default(0);
            $table->unsignedInteger('words_removed')->default(0);
            $table->unsignedInteger('manuscript_words');
            $table->timestamp('recorded_at');
            $table->index(['manuscript_id', 'recorded_at']);
            $table->index(['author_id', 'recorded_at']);
        });
        DB::table('studio_chapters')->orderBy('id')->chunkById(100, function ($chapters): void {
            foreach ($chapters as $chapter) {
                DB::table('studio_chapters')->where('id', $chapter->id)->update(['word_count' => WritingActivity::wordCount($chapter->content_md ?? '')]);
            }
        });
        DB::table('studio_manuscripts')->orderBy('id')->chunkById(100, function ($manuscripts): void {
            foreach ($manuscripts as $manuscript) {
                DB::table('studio_writing_activity')->insert([
                    'manuscript_id' => $manuscript->id, 'author_id' => $manuscript->author_id, 'kind' => 'baseline',
                    'manuscript_words' => DB::table('studio_chapters')->where('manuscript_id', $manuscript->id)->whereNull('deleted_at')->sum('word_count'),
                    'recorded_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_writing_activity');
        Schema::table('studio_chapters', fn (Blueprint $table) => $table->dropColumn('word_count'));
        Schema::table('studio_manuscripts', fn (Blueprint $table) => $table->dropColumn('weekly_word_goal'));
    }
};
