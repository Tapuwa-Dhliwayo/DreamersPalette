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
        return collect($this->reads ?? [])->contains(fn ($read) => $read['start'] <= $start && $read['end'] >= $end);
    }

    public function recordRead(int $start, int $end, string $notes): void
    {
        $reads = $this->reads ?? [];
        $reads[] = ['start' => $start, 'end' => $end, 'notes' => $notes];
        $this->update(['reads' => $reads]);
    }
}
