<?php

namespace App\Services\Worldbuilding;

use App\Models\MagicSystem;
use App\Models\StoryArc;
use App\Models\World;
use App\Models\WorldEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class WorldContext
{
    public function catalogue(World $world): array
    {
        $records = [];
        foreach (['person' => 'people', 'entry' => 'entries', 'event' => 'events', 'relationship' => 'relationships', 'system' => 'systems', 'arc' => 'arcs', 'knowledge' => 'knowledge', 'investigation' => 'investigations', 'map' => 'maps', 'alignment' => 'alignmentCharts', 'comparison' => 'comparisons'] as $kind => $relation) {
            foreach ($world->{$relation}()->get() as $record) {
                $records[$kind.':'.$record->id] = ['key' => $kind.':'.$record->id, 'kind' => $kind, 'name' => $record->name ?? $record->title ?? $record->label, 'revision' => $record->revision];
            }
        }

        return $records;
    }

    public function url(World $world, string $key): string
    {
        [$kind,$id] = explode(':', $key);
        $section = match ($kind) {
            'person' => 'people','entry' => 'entries','event' => 'timeline','arc' => 'arcs','alignment' => 'alignments','relationship' => 'relationships','comparison' => 'comparisons','investigation' => 'investigations','knowledge' => 'knowledge',default => $kind,
        };
        $route = match ($kind) {
            'system' => 'studio.world', 'map' => 'studio.world.maps', 'relationship','comparison' => 'studio.world.thinking', 'knowledge','investigation' => 'studio.world.reasoning', default => 'studio.world.develop',
        };

        return route($route, ['world' => $world, 'section' => $section, $kind === 'system' ? 'system' : 'selected' => $id]);
    }

    public function target(World $world, string $key, bool $archived = false): Model
    {
        if (! preg_match('/^(person|entry|event|relationship|system|arc|knowledge|investigation|map|alignment|comparison):([1-9][0-9]*)$/D', $key, $parts)) {
            throw ValidationException::withMessages(['context' => 'Choose a record from this world.']);
        }
        $relation = match ($parts[1]) {
            'person' => 'people', 'entry' => 'entries', 'event' => 'events', 'relationship' => 'relationships', 'system' => 'systems', 'arc' => 'arcs', 'knowledge' => 'knowledge', 'investigation' => 'investigations', 'map' => 'maps', 'alignment' => 'alignmentCharts', 'comparison' => 'comparisons',
        };
        $query = $world->{$relation}();

        return ($archived ? $query->withTrashed() : $query)->whereKey($parts[2])->firstOrFail();
    }

    public function snapshot(Model $record): array
    {
        $snapshot = $record->attributesToArray();
        unset($snapshot['author_id'], $snapshot['updated_by'], $snapshot['archived_by']);
        if ($record instanceof MagicSystem) {
            $snapshot['rules'] = $record->rules()->get()->toArray();
        }
        if ($record instanceof WorldEvent) {
            $snapshot['participants'] = $record->people()->pluck('studio_world_people.id')->all();
        }
        if ($record instanceof StoryArc) {
            $snapshot['beats'] = $record->beats()->get()->toArray();
        }

        return $snapshot;
    }
}
