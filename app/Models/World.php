<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class World extends Model
{
    use ArchivesStudioRecords;

    protected $table = 'studio_worlds';

    protected $fillable = ['name', 'description'];

    protected function casts(): array
    {
        return ['author_id' => 'integer'];
    }

    public function systems(): HasMany
    {
        return $this->hasMany(MagicSystem::class)->orderBy('name');
    }

    public function manuscripts(): HasMany
    {
        return $this->hasMany(Manuscript::class)->orderBy('title');
    }

    public function people(): HasMany
    {
        return $this->hasMany(WorldPerson::class)->orderBy('name');
    }

    public function events(): HasMany
    {
        return $this->hasMany(WorldEvent::class)->orderBy('position')->orderBy('id');
    }

    public function arcs(): HasMany
    {
        return $this->hasMany(StoryArc::class)->orderBy('title');
    }

    public function alignmentCharts(): HasMany
    {
        return $this->hasMany(AlignmentChart::class)->orderBy('title');
    }

    public function calendars(): HasMany
    {
        return $this->hasMany(WorldCalendar::class)->orderBy('name');
    }

    public function maps(): HasMany
    {
        return $this->hasMany(WorldMap::class)->orderBy('title');
    }

    public function investigations(): HasMany
    {
        return $this->hasMany(WorldInvestigation::class)->orderBy('title');
    }

    public function knowledge(): HasMany
    {
        return $this->hasMany(WorldKnowledge::class)->orderBy('title');
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(WorldRelationship::class)->orderBy('label');
    }

    public function comparisons(): HasMany
    {
        return $this->hasMany(WorldComparison::class)->orderBy('title');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(WorldEntry::class)->orderBy('name');
    }

    public function dependencyImpact(string $changedKey): array
    {
        $dependencies = $this->relationships()->where('is_dependency', true)->get();
        $names = [];
        foreach ($dependencies as $dependency) {
            $names[$dependency->source_key] = $dependency->source_name;
            $names[$dependency->target_key] = $dependency->target_name;
        }
        $queue = [[$changedKey]];
        $visited = [$changedKey => true];
        $affected = [];
        $truncated = false;
        for ($cursor = 0; $cursor < count($queue); $cursor++) {
            $path = $queue[$cursor];
            $current = $path[array_key_last($path)];
            foreach ($dependencies->where('target_key', $current) as $dependency) {
                $dependent = $dependency->source_key;
                if (isset($visited[$dependent])) {
                    continue;
                }
                if (count($affected) >= 2000) {
                    $truncated = true;
                    break 2;
                }
                $visited[$dependent] = true;
                $next = [...$path, $dependent];
                $affected[] = ['key' => $dependent, 'name' => $dependency->source_name, 'path' => $next, 'path_names' => array_map(fn ($key) => $names[$key] ?? $key, $next)];
                $queue[] = $next;
            }
        }

        // Converging paths can hide a cycle from a breadth-first path alone.
        $indegree = array_fill_keys(array_keys($visited), 0);
        $adjacency = [];
        foreach ($dependencies as $dependency) {
            if (! isset($visited[$dependency->source_key], $visited[$dependency->target_key])) {
                continue;
            }
            $adjacency[$dependency->target_key][] = $dependency->source_key;
            $indegree[$dependency->source_key]++;
        }
        $ready = array_keys(array_filter($indegree, fn ($degree) => $degree === 0));
        $removed = 0;
        for ($cursor = 0; $cursor < count($ready); $cursor++) {
            $removed++;
            foreach ($adjacency[$ready[$cursor]] ?? [] as $dependent) {
                if (--$indegree[$dependent] === 0) {
                    $ready[] = $dependent;
                }
            }
        }
        $cycle = $removed < count($visited);

        return ['affected' => $affected, 'cycle' => $cycle, 'truncated' => $truncated];
    }

    public function referenceTarget(string $kind, int $id, bool $archived = false): Model
    {
        $query = match ($kind) {
            'entry' => $this->entries(), 'person' => $this->people(), 'system' => $this->systems(), default => abort(404)
        };

        $target = $query->withTrashed()->whereKey($id)->firstOrFail();
        if (! $archived && $target->trashed()) {
            throw ValidationException::withMessages(['conflict' => 'This world record was archived. Restore it before making a new link.']);
        }

        return $target;
    }

    public function referenceCatalogue(): Collection
    {
        return collect(['entry' => $this->entries()->with('type')->get(), 'person' => $this->people()->get(), 'system' => $this->systems()->get()])->flatMap(fn ($records, $kind) => $records->map(fn ($record) => [
            'key' => $kind.':'.$record->id, 'kind' => $kind, 'id' => $record->id, 'name' => $record->name,
            'type' => match ($kind) {
                'entry' => $record->type->name, 'person' => 'Character', 'system' => 'Magic system'
            },
            'aliases' => $record->aliases,
        ]))->sortBy('name')->values();
    }

    public function captureDraftContext(Manuscript $manuscript): array
    {
        // Finish already holds the manuscript lock; variation writes use the same lock order.
        $systems = $this->systems()->reorder('id')->lockForUpdate()->get();

        return ['world_id' => $this->id, 'world_name' => $this->name,
            'references' => ChapterReference::where('manuscript_id', $manuscript->id)->whereIn('chapter_id', $manuscript->chapters()->select('id'))->orderBy('id')->get()->toArray(),
            'development' => [
                'calendars' => $this->calendars()->get()->toArray(),
                'investigations' => $this->investigations()->get()->toArray(),
                'knowledge' => $this->knowledge()->where(fn ($query) => $query->whereNull('manuscript_id')->orWhere('manuscript_id', $manuscript->id))->with('revealChapter:id,manuscript_id,title,position,deleted_at')->get()->toArray(),
                'maps' => $this->maps()->get()->toArray(),
                'relationships' => $this->relationships()->get()->toArray(),
                'comparisons' => $this->comparisons()->get()->toArray(),
                'entries' => $this->entries()->with('type')->get()->toArray(),
                'people' => $this->people()->get()->toArray(),
                'events' => $this->events()->with(['people:id,name', 'calendar'])->get()->map(fn ($event) => [...$event->toArray(), 'calendar_label' => $event->calendarLabel()])->all(),
                'arcs' => $this->arcs()->with('beats')->get()->toArray(),
                'alignments' => $this->alignmentCharts()->with('placements')->get()->toArray(),
            ],
            'systems' => $systems->map(fn ($system) => [
                'system_id' => $system->id, 'name' => $system->name, 'revision' => $system->revision,
                'version_id' => $system->versions()->where('revision', $system->revision)->lockForUpdate()->value('id'),
                'variations' => $system->variations()->where('manuscript_id', $manuscript->id)->with('mode')->get()->toArray(),
            ])->all()];
    }
}
