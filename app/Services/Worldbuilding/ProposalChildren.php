<?php

namespace App\Services\Worldbuilding;

use App\Models\User;
use App\Models\WorldExercise;
use Illuminate\Database\Eloquent\Model;

class ProposalChildren
{
    public function apply(string $kind, Model $parent, array $fields, WorldExercise $exercise, array $resolved): void
    {
        $createdRules = [];
        foreach (ProposalTypes::definition($kind)['children'] ?? [] as $collection => $relation) {
            if (! array_key_exists($collection, $fields)) {
                continue;
            }
            $retained = [];
            foreach ($fields[$collection] as $index => $child) {
                $record = ! empty($child['id']) ? $parent->{$relation}()->lockForUpdate()->findOrFail($child['id']) : $parent->{$relation}()->make();
                if ($record->exists && (int) $record->revision !== (int) ($child['revision'] ?? 0)) {
                    ProposalTypes::invalid('A child record changed before acceptance.');
                }
                $attributes = collect($child)->only($record->getFillable())->all();
                if ($collection === 'beats') {
                    if (array_key_exists('event_id', $child) || array_key_exists('event_ref', $child) || ! $record->exists) {
                        $attributes['event_id'] = $this->event($child, $exercise, $resolved);
                    }
                    if (array_key_exists('intensity', $child) || ! $record->exists) {
                        $attributes['intensity'] = $child['intensity'] ?? null;
                    }
                }
                if ($collection === 'rules' && ! $record->exists) {
                    $attributes += ['conditions_md' => '', 'consequences_md' => '', 'exceptions_md' => '', 'reasoning_md' => '', 'decision_id' => 1];
                }
                if ($collection === 'cases' && ! $record->exists) {
                    $attributes += ['outcome_md' => '', 'reasoning_md' => ''];
                }
                $record->fill($attributes);
                $record->revision = $record->exists ? $record->revision + 1 : 1;
                $record->save();
                $retained[] = $record->id;
                if ($collection === 'rules') {
                    $createdRules[$index] = $record->id;
                }
                if ($collection === 'cases' && (isset($child['rule_ids']) || isset($child['rule_refs']) || ! $record->wasRecentlyCreated)) {
                    $ids = $child['rule_ids'] ?? $record->rules()->pluck('studio_magic_rules.id')->all();
                    foreach ($child['rule_refs'] ?? [] as $ruleIndex) {
                        if (! isset($createdRules[$ruleIndex])) {
                            ProposalTypes::invalid('A case references a missing proposed rule.');
                        }
                        $ids[] = $createdRules[$ruleIndex];
                    }
                    $record->rules()->sync(array_unique($ids));
                }
            }
            if (($fields['children_mode'] ?? 'merge') === 'replace') {
                foreach ($parent->{$relation}()->whereNotIn('id', $retained)->lockForUpdate()->get() as $removed) {
                    $removed->setArchived(true, $exercise->world->author_id, $removed->revision);
                }
            }
        }
        if ($kind === 'event' && array_key_exists('person_ids', $fields)) {
            $parent->people()->sync($fields['person_ids']);
        }
    }

    private function event(array $beat, WorldExercise $exercise, array $resolved): ?int
    {
        if (! array_key_exists('event_ref', $beat)) {
            return $beat['event_id'] ?? null;
        }
        $reference = $beat['event_ref'];
        if (! $reference) {
            return null;
        }
        if (str_starts_with($reference, 'event:')) {
            return $exercise->world->events()->withTrashed()->findOrFail((int) substr($reference, 6))->id;
        }
        if (! str_starts_with($reference, 'board:')) {
            ProposalTypes::invalid('An event proposal reference was not staged.');
        }
        $id = (int) substr($reference, 6);
        $idea = $exercise->ideas()->findOrFail($id);
        $key = $resolved[$id] ?? $idea->target_key;
        if (! $key || ! str_starts_with($key, 'event:')) {
            ProposalTypes::invalid('Include the beat’s event proposal in this acceptance.');
        }

        return $exercise->world->events()->withTrashed()->findOrFail((int) substr($key, 6))->id;
    }

    public function restore(string $kind, Model $parent, ?array $before, User $author): void
    {
        foreach (ProposalTypes::definition($kind)['children'] ?? [] as $collection => $relation) {
            $original = collect($before[$collection] ?? [])->keyBy('id');
            foreach ($parent->{$relation}()->withTrashed()->lockForUpdate()->get() as $record) {
                $saved = $original->get($record->id);
                if (! $saved) {
                    if (! $record->trashed()) {
                        $record->setArchived(true, $author->id, $record->revision);
                    }

                    continue;
                }
                $record->fill(collect($saved)->only($record->getFillable())->all());
                $record->forceFill(['deleted_at' => $saved['deleted_at'] ?? null, 'archived_by' => $saved['archived_by'] ?? null, 'revision' => $record->revision + 1])->save();
                if ($collection === 'cases') {
                    $record->rules()->sync($saved['rule_ids'] ?? []);
                }
            }
        }
        if ($kind === 'event') {
            $parent->people()->sync($before['participants'] ?? []);
        }
    }
}
