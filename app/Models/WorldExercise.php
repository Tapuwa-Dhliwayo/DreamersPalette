<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorldExercise extends Model
{
    protected $table = 'studio_world_exercises';

    protected $fillable = ['title', 'purpose', 'instructions', 'summary', 'summary_through_id', 'context_keys', 'passages', 'connection_id'];

    protected function casts(): array
    {
        return ['world_id' => 'integer', 'summary_through_id' => 'integer', 'connection_id' => 'integer', 'context_keys' => 'array', 'passages' => 'array', 'revision' => 'integer'];
    }

    use ArchivesStudioRecords;
    use RevisesWorldRecord;

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function ideas(): HasMany
    {
        return $this->hasMany(SandboxIdea::class, 'exercise_id')->orderBy('position')->orderBy('id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AssistantMessage::class, 'exercise_id')->orderBy('id');
    }

    public function turns(): HasMany
    {
        return $this->hasMany(AssistantTurn::class, 'exercise_id')->orderByDesc('id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(SandboxLink::class, 'exercise_id');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(CanonChange::class, 'exercise_id')->latest('id');
    }

    /**
     * Lays every active idea out without overlaps, `columns` cards wide: characters on the first
     * row, journeys next, then other kinds, then events in story order. Returns the previous
     * positions keyed by idea id, with the revision each idea now has, so the layout can be undone.
     */
    public function tidyBoard(bool $compactCards, int $columns): array
    {
        $columns = max(1, min($columns, 12));
        $rowHeight = ($compactCards ? 112 : 160) + 24;
        $ideas = $this->ideas()->get();
        $kindOrder = ['person' => 0, 'arc' => 1];
        $groups = $ideas->where('kind', '!=', 'event')
            ->groupBy('kind')
            ->sortKeysUsing(fn (string $a, string $b): int => [$kindOrder[$a] ?? 2, $a] <=> [$kindOrder[$b] ?? 2, $b])
            ->values()
            ->push($ideas->where('kind', 'event'));
        $positions = [];
        $row = 0;
        foreach ($groups as $group) {
            foreach ($group->values() as $index => $idea) {
                $positions[$idea->id] = ['x' => 40 + ($index % $columns) * 260, 'y' => 40 + ($row + intdiv($index, $columns)) * $rowHeight];
            }
            $row += (int) ceil($group->count() / $columns);
        }

        return $this->moveIdeas($ideas, $positions);
    }

    public function restoreBoard(array $previous): void
    {
        $ideas = $this->ideas()->whereIn('id', array_keys($previous))->get();
        foreach ($ideas as $idea) {
            $saved = $previous[$idea->id];
            $idea->revise(['x' => $saved['x'], 'y' => $saved['y']], $saved['revision']);
        }
    }

    private function moveIdeas($ideas, array $positions): array
    {
        $previous = [];
        foreach ($ideas as $idea) {
            $target = $positions[$idea->id];
            if ($idea->x === $target['x'] && $idea->y === $target['y']) {
                continue;
            }
            $previous[$idea->id] = ['x' => $idea->x, 'y' => $idea->y, 'revision' => $idea->revision + 1];
            // Layout stays editable after canonisation; it never changes accepted content.
            $idea->revise($target, $idea->revision);
        }

        return $previous;
    }
}
