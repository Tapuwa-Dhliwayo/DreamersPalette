<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WritingActivity extends Model
{
    public $timestamps = false;

    protected $table = 'studio_writing_activity';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'immutable_datetime',
            'words_added' => 'integer',
            'words_removed' => 'integer',
            'manuscript_words' => 'integer',
        ];
    }

    public static function words(string $markdown): array
    {
        $text = preg_replace('/!?\[([^\]]*)\]\([^)]+\)/u', '$1', $markdown);
        preg_match_all("/[\\p{L}\\p{N}]+(?:[’'-][\\p{L}\\p{N}]+)*/u", strip_tags($text), $matches);

        return $matches[0];
    }

    public static function wordCount(string $markdown): int
    {
        return count(self::words($markdown));
    }

    public static function changes(string $before, string $after): array
    {
        // Compare word frequencies so moving existing prose does not count as new writing.
        $beforeCounts = array_count_values(self::words($before));
        $afterCounts = array_count_values(self::words($after));
        $added = $removed = 0;
        foreach ($afterCounts as $word => $count) {
            $added += max(0, $count - ($beforeCounts[$word] ?? 0));
        }
        foreach ($beforeCounts as $word => $count) {
            $removed += max(0, $count - ($afterCounts[$word] ?? 0));
        }

        return ['words_added' => $added, 'words_removed' => $removed];
    }
}
