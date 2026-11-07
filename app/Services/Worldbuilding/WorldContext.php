<?php

namespace App\Services\Worldbuilding;

use App\Models\AlignmentChart;
use App\Models\MagicSystem;
use App\Models\StoryArc;
use App\Models\World;
use App\Models\WorldCalendar;
use App\Models\WorldEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class WorldContext
{
    public function catalogue(World $world, bool $includeArchived = false): array
    {
        $records = [];
        foreach (ProposalTypes::definitions() as $kind => $definition) {
            if (! isset($definition['relation'])) {
                continue;
            }
            $relation = $definition['relation'];
            foreach ($world->{$relation}()->when($includeArchived, fn ($query) => $query->withTrashed())->get() as $record) {
                $records[$kind.':'.$record->id] = [
                    'key' => $kind.':'.$record->id,
                    'kind' => $kind,
                    'name' => ($record->name ?? $record->title ?? $record->label).($includeArchived && $record->trashed() ? ' · Archived' : ''),
                    'archived' => $record->trashed(),
                    'revision' => $record->revision,
                    'aliases' => $record->aliases ?? [],
                ];
            }
        }

        return $records;
    }

    public function url(World $world, string $key): string
    {
        [$kind,$id] = explode(':', $key);
        $section = match ($kind) {
            'person' => 'people','entry' => 'entries','event' => 'timeline','arc' => 'arcs','alignment' => 'alignments','relationship' => 'relationships','comparison' => 'comparisons', 'calendar' => 'calendars','investigation' => 'investigations','knowledge' => 'knowledge',default => $kind,
        };
        $route = match ($kind) {
            'calendar' => 'studio.world.calendars', 'system' => 'studio.world', 'map' => 'studio.world.maps', 'relationship','comparison' => 'studio.world.thinking', 'knowledge','investigation' => 'studio.world.reasoning', default => 'studio.world.develop',
        };

        return route($route, ['world' => $world, 'section' => $section, $kind === 'system' ? 'system' : 'selected' => $id]);
    }

    public function target(World $world, string $key, bool $archived = false): Model
    {
        if (! preg_match('/^([a-z]+):([1-9][0-9]*)$/D', $key, $parts) || ! isset(ProposalTypes::definitions()[$parts[1]]['relation'])) {
            throw ValidationException::withMessages(['context' => 'Choose a record from this world.']);
        }
        $relation = ProposalTypes::definition($parts[1])['relation'];
        $query = $world->{$relation}();

        return ($archived ? $query->withTrashed() : $query)->whereKey($parts[2])->firstOrFail();
    }

    public function snapshot(Model $record, bool $archivedChildren = false): array
    {
        $snapshot = $record->attributesToArray();
        unset($snapshot['author_id'], $snapshot['updated_by'], $snapshot['archived_by']);
        if ($record instanceof MagicSystem) {
            $snapshot['rules'] = $record->rules()->when($archivedChildren, fn ($query) => $query->withTrashed())->reorder('id')->get()->toArray();
            $snapshot['cases'] = $record->cases()->when($archivedChildren, fn ($query) => $query->withTrashed())->reorder('id')->get()->map(fn ($case) => $case->attributesToArray() + ['rule_ids' => $case->rules()->pluck('studio_magic_rules.id')->sort()->values()->all()])->all();
        }
        if ($record instanceof WorldEvent) {
            $snapshot['participants'] = $record->people()->pluck('studio_world_people.id')->all();
        }
        if ($record instanceof StoryArc) {
            $snapshot['beats'] = $record->beats()->when($archivedChildren, fn ($query) => $query->withTrashed())->reorder('id')->get()->toArray();
        }

        if ($record instanceof AlignmentChart) {
            $snapshot['placements'] = $record->placements()->when($archivedChildren, fn ($query) => $query->withTrashed())->reorder('id')->get()->toArray();
        }

        if ($record instanceof WorldCalendar) {
            $snapshot['events'] = WorldEvent::withTrashed()->where('calendar_id', $record->id)->orderBy('id')->get(['id', 'revision', 'calendar_day'])->toArray();
        }

        return $snapshot;
    }
}
