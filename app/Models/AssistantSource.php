<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantSource extends Model
{
    protected $table = 'studio_assistant_sources';

    protected $fillable = ['chapter_id', 'chapter_revision', 'manuscript_id', 'position', 'title', 'passages', 'reads'];

    protected function casts(): array
    {
        return ['chapter_id' => 'integer', 'chapter_revision' => 'integer', 'passages' => 'array', 'reads' => 'array'];
    }

    public function turn(): BelongsTo
    {
        return $this->belongsTo(AssistantTurn::class, 'turn_id');
    }

    public function window(int $start, int $end): array
    {
        abort_unless($start >= 1 && $end >= $start && $end <= count($this->passages), 422);

        return array_map(fn ($number) => ['number' => $number, 'text' => $this->passages[$number - 1]], range($start, $end));
    }

    public function hasRead(int $start, int $end): bool
    {
        $cursor = $start;
        foreach (collect($this->reads ?? [])->filter(fn ($read) => self::isAnalysis($read))->sortBy('start') as $read) {
            if ($read['end'] < $cursor) {
                continue;
            }
            if ($read['start'] > $cursor) {
                return false;
            }
            $cursor = max($cursor, $read['end'] + 1);
            if ($cursor > $end) {
                return true;
            }
        }

        return false;
    }

    // Reads saved before `kind` existed all came from manuscript analysis, so a missing kind means analysed.
    public static function isAnalysis(array $read): bool
    {
        return ($read['kind'] ?? 'analysed') !== 'consulted';
    }

    public function recordRead(int $start, int $end, string $notes, string $kind = 'analysed'): void
    {
        $reads = $this->reads ?? [];
        $reads[] = ['start' => $start, 'end' => $end, 'notes' => $notes, 'kind' => $kind];
        $this->update(['reads' => $reads]);
    }
}
