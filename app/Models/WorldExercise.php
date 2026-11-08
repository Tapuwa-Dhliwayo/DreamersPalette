<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

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

    public function moveEvent(int $sourceId, int $sourceRevision, int $targetId, int $targetRevision, bool $after): array
    {
        return $this->getConnection()->transaction(function () use ($sourceId, $sourceRevision, $targetId, $targetRevision, $after): array {
            $exercise = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            $source = $exercise->ideas()->lockForUpdate()->findOrFail($sourceId);
            $target = $exercise->ideas()->lockForUpdate()->findOrFail($targetId);
            abort_unless($source->kind === 'event' && $target->kind === 'event' && $source->canEdit() && $target->canEdit(), 422);
            if ($source->revision !== $sourceRevision || $target->revision !== $targetRevision) {
                throw ValidationException::withMessages(['timeline' => 'These events changed. Refresh the timeline before moving them.']);
            }
            if ($source->alternative !== $target->alternative) {
                throw ValidationException::withMessages(['timeline' => 'Move events within the same scenario. Fork an event to explore a different scenario.']);
            }
            if ($sourceId === $targetId) {
                return [];
            }
            $ordered = $exercise->ideas()->where('kind', 'event')->where('alternative', $target->alternative)->where('lane', $target->lane)->whereNull('canonised_at')->lockForUpdate()->get()->reject(fn ($idea) => $idea->id === $sourceId)->values();
            $index = $ordered->search(fn ($idea) => $idea->id === $targetId);
            $ordered->splice($index + ($after ? 1 : 0), 0, [$source]);
            $moved = [];
            foreach ($ordered as $index => $idea) {
                $moved[$idea->id] = ['previous_revision' => $idea->revision, 'revision' => $idea->revision + 1];
                $idea->revise(['position' => ($index + 1) * 10, 'lane' => $target->lane], $idea->revision);
            }

            return $moved;
        });
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
