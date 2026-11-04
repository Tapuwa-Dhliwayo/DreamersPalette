<?php

namespace App\Services\Worldbuilding;

use App\Models\CanonChange;
use App\Models\MagicSystem;
use App\Models\SandboxIdea;
use App\Models\User;
use App\Models\World;
use App\Models\WorldEntryType;
use App\Models\WorldExercise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Canonisation
{
    private const RELATIONS = ['person' => 'people', 'entry' => 'entries', 'event' => 'events', 'relationship' => 'relationships', 'system' => 'systems', 'arc' => 'arcs', 'knowledge' => 'knowledge', 'investigation' => 'investigations'];

    public function __construct(private WorldContext $context) {}

    public function review(WorldExercise $exercise, array $ids): array
    {
        Gate::authorize('update', $exercise);
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);
        if (! $ids || count($ids) > 50) {
            $this->conflict('Select between one and fifty ideas to review.');
        }
        $ideas = $exercise->ideas()->whereKey($ids)->get();
        if ($ideas->count() !== count($ids)) {
            $this->conflict('One of the selected ideas is unavailable. Refresh the selection.');
        }
        $changes = [];
        $targets = [];
        foreach ($ideas as $idea) {
            if (! $idea->canEdit() || ! isset(self::RELATIONS[$idea->kind])) {
                $this->conflict('Choose an editable record proposal. Thoughts, questions and name shortlists remain on the board; assign a name to a character before canonising it.');
            }
            if ($idea->uncertainty) {
                $this->conflict('Resolve or clear the uncertainty on “'.$idea->title.'” before making it canon.');
            }
            $target = $idea->target_key ? $this->context->target($exercise->world, $idea->target_key) : null;
            if ($target && (! str_starts_with($idea->target_key, $idea->kind.':') || (int) $target->revision !== $idea->target_revision)) {
                $this->conflict('An established record changed. Reopen the idea against its current revision before reviewing.');
            }
            if ($target && isset($targets[$idea->target_key])) {
                $this->conflict('Two proposals update the same record. Review them separately.');
            }
            $targets[$idea->target_key ?? 'idea:'.$idea->id] = true;
            $fields = $this->fields($idea, $exercise, $target);
            $changes[] = ['idea_id' => $idea->id, 'idea_revision' => $idea->revision, 'kind' => $idea->kind, 'title' => $idea->title, 'target_key' => $idea->target_key, 'before' => $target ? $this->context->snapshot($target) : null, 'after' => $fields];
        }
        $links = $exercise->links()->where(fn ($query) => $query->whereIn('source_id', $ids)->orWhereIn('target_id', $ids))->orderBy('id')->get()->map(function ($link) use ($exercise, $ids): array {
            if (in_array($link->kind, ['participant', 'relationship', 'depends', 'before'], true)) {
                foreach ([$link->source_id, $link->target_id] as $id) {
                    $endpoint = $exercise->ideas()->findOrFail($id);
                    if (! in_array($id, $ids, true) && ! $endpoint->canonised_at && ! $endpoint->target_key) {
                        $this->conflict('Include both ends of “'.$link->label.'”, or link its other end to an established record.');
                    }
                }
            }

            return $link->only(['id', 'source_id', 'target_id', 'kind', 'label', 'notes_md', 'revision']);
        })->all();
        $review = ['exercise_id' => $exercise->id, 'ids' => $ids, 'changes' => $changes, 'links' => $links];
        $review['fingerprint'] = hash('sha256', json_encode($review, JSON_THROW_ON_ERROR));

        return $review;
    }

    public function accept(WorldExercise $exercise, array $review, string $requestUuid): CanonChange
    {
        Gate::authorize('update', $exercise);

        return DB::transaction(function () use ($exercise, $review, $requestUuid): CanonChange {
            $world = World::lockForUpdate()->findOrFail($exercise->world_id);
            $exercise = WorldExercise::lockForUpdate()->findOrFail($exercise->id);
            Gate::authorize('update', $exercise);
            if ($existing = $exercise->changes()->where('request_uuid', $requestUuid)->first()) {
                return $existing;
            }
            // The world lock serialises normal world writes; also lock every proposal and target for stale checks.
            $exercise->ideas()->lockForUpdate()->get();
            foreach ($review['changes'] ?? [] as $change) {
                if ($change['target_key']) {
                    $target = $this->context->target($world, $change['target_key']);
                    $target->newQuery()->whereKey($target->id)->lockForUpdate()->firstOrFail();
                }
            }
            $current = $this->review($exercise, $review['ids'] ?? []);
            if (! hash_equals($current['fingerprint'], $review['fingerprint'] ?? '')) {
                $this->conflict('The proposals or their connections changed. Review the latest changes before accepting.');
            }
            $changes = [];
            $resolved = [];
            $author = User::findOrFail($world->author_id);
            foreach ($current['changes'] as $change) {
                $idea = $exercise->ideas()->findOrFail($change['idea_id']);
                $target = $change['target_key'] ? $this->context->target($world, $change['target_key']) : null;
                $before = $target ? $this->context->snapshot($target) : null;
                if ($target instanceof MagicSystem) {
                    $target->saveNotes($change['after'], $target->revision, $author);
                    $target->refresh();
                } elseif ($target) {
                    $target->revise($change['after'], $target->revision);
                    $target->refresh();
                } else {
                    $target = $change['kind'] === 'system' ? MagicSystem::start($world, $change['after'], $author) : $world->{self::RELATIONS[$change['kind']]}()->create($change['after']);
                    $target->refresh();
                }
                $key = $change['kind'].':'.$target->id;
                $resolved[$idea->id] = $key;
                $changes[] = ['key' => $key, 'idea_id' => $idea->id, 'before' => $before, 'after' => $this->context->snapshot($target)];
                $idea->forceFill(['target_key' => $key, 'target_revision' => $target->revision, 'canonised_at' => now(), 'revision' => $idea->revision + 1])->save();
            }
            foreach ($current['links'] as $link) {
                if ($link['kind'] === 'connection') {
                    continue;
                }
                $source = $this->endpoint($exercise, $link['source_id'], $resolved);
                $target = $this->endpoint($exercise, $link['target_id'], $resolved);
                if ($link['kind'] === 'before') {
                    if (! str_starts_with($source, 'event:') || ! str_starts_with($target, 'event:') || (float) $this->context->target($world, $source)->position >= (float) $this->context->target($world, $target)->position) {
                        $this->conflict('A “before” link conflicts with the proposed event order. Adjust the event positions and review again.');
                    }

                    continue;
                }
                if ($link['kind'] === 'participant') {
                    $eventKey = str_starts_with($source, 'event:') ? $source : $target;
                    $personKey = str_starts_with($source, 'person:') ? $source : $target;
                    if (! str_starts_with($eventKey, 'event:') || ! str_starts_with($personKey, 'person:')) {
                        $this->conflict('Participation must connect one character and one event.');
                    }
                    $event = $this->context->target($world, $eventKey);
                    $person = $this->context->target($world, $personKey);
                    if (! $event->people()->where('studio_world_people.id', $person->id)->exists()) {
                        $event->people()->attach($person->id);
                        $event->increment('revision');
                        $changes[] = ['participant' => ['event' => $eventKey, 'person' => $personKey], 'after_revision' => $event->revision];
                    }

                    continue;
                }
                if (! preg_match('/^(person|entry|system):/', $source) || ! preg_match('/^(person|entry|system):/', $target) || $source === $target) {
                    $this->conflict('Established relationships currently connect distinct characters, encyclopedia entries or magic systems. Keep other links as board connections.');
                }
                $record = $world->relationships()->firstOrCreate(['source_key' => $source, 'target_key' => $target, 'label' => $link['label'], 'is_dependency' => $link['kind'] === 'depends'], ['source_name' => $this->name($world, $source), 'target_name' => $this->name($world, $target), 'notes_md' => $link['notes_md']]);
                if ($record->wasRecentlyCreated) {
                    $changes[] = ['key' => 'relationship:'.$record->id, 'idea_id' => null, 'before' => null, 'after' => $this->context->snapshot($record->refresh())];
                }
            }
            // Capture final revisions after participant changes for safe reversal.
            foreach ($changes as &$change) {
                if (isset($change['key'])) {
                    $change['after'] = $this->context->snapshot($this->context->target($world, $change['key']));
                }
            }
            unset($change);

            return $exercise->changes()->create(['user_id' => $author->id, 'request_uuid' => $requestUuid, 'changes' => $changes]);
        });
    }

    public function reverse(WorldExercise $exercise, int $changeId): void
    {
        Gate::authorize('update', $exercise);
        DB::transaction(function () use ($exercise, $changeId): void {
            $world = World::lockForUpdate()->findOrFail($exercise->world_id);
            $change = $exercise->changes()->lockForUpdate()->findOrFail($changeId);
            if ($change->reversed_at) {
                return;
            }
            foreach ($change->changes as $entry) {
                $key = $entry['key'] ?? $entry['participant']['event'];
                $target = $this->context->target($world, $key);
                $target = $target->newQuery()->lockForUpdate()->findOrFail($target->id);
                $revision = $entry['after']['revision'] ?? $entry['after_revision'];
                if ($target->revision !== $revision) {
                    $this->conflict('A record has changed since this acceptance. Its newer writing cannot be overwritten by Undo.');
                }
                if (($entry['before'] ?? null) === null && isset($entry['key'])) {
                    $this->requireNoNewLinks($world, $entry['key'], $change->changes);
                }
            }
            foreach (array_reverse($change->changes) as $entry) {
                if (isset($entry['participant'])) {
                    $event = $this->context->target($world, $entry['participant']['event']);
                    $event->people()->detach((int) explode(':', $entry['participant']['person'])[1]);
                    $event->increment('revision');

                    continue;
                }
                $target = $this->context->target($world, $entry['key']);
                if ($entry['before'] === null) {
                    $target->setArchived(true, auth()->id());
                } elseif ($target instanceof MagicSystem) {
                    $target->saveNotes(collect($entry['before'])->only($target->getFillable())->all(), $target->revision, auth()->user());
                } else {
                    $target->revise(collect($entry['before'])->only($target->getFillable())->all(), $target->revision);
                }
                if ($entry['idea_id']) {
                    $idea = $exercise->ideas()->findOrFail($entry['idea_id']);
                    $idea->forceFill(['canonised_at' => null, 'target_key' => $entry['before'] ? $entry['key'] : null, 'target_revision' => $entry['before'] ? $target->fresh()->revision : null, 'revision' => $idea->revision + 1])->save();
                }
            }
            $change->update(['reversed_at' => now()]);
        });
    }

    private function fields(SandboxIdea $idea, WorldExercise $exercise, ?Model $target): array
    {
        $custom = $idea->fields ?? [];
        $fields = match ($idea->kind) {
            'person' => ['name' => $idea->title, 'notes_md' => $idea->notes_md, 'aliases' => $custom['aliases'] ?? $target?->aliases ?? [], 'roles' => $custom['roles'] ?? $target?->roles ?? [], 'domain' => $custom['domain'] ?? $target?->domain],
            'event' => ['title' => $idea->title, 'notes_md' => $idea->notes_md, 'position' => $idea->position, 'date_label' => $idea->date_label],
            'entry' => ['name' => $idea->title, 'notes_md' => $idea->notes_md, 'aliases' => $custom['aliases'] ?? $target?->aliases ?? [], 'type_id' => $custom['type_id'] ?? $target?->type_id],
            'system' => ['name' => $idea->title, 'notes_md' => $idea->notes_md, 'aliases' => $custom['aliases'] ?? $target?->aliases ?? []],
            'arc' => ['title' => $idea->title, 'notes_md' => $idea->notes_md, 'measure' => $custom['measure'] ?? $target?->measure ?? 'Pressure'],
            'knowledge' => ['title' => $idea->title, 'truth_md' => $custom['truth_md'] ?? $idea->notes_md, 'notes_md' => $custom['notes_md'] ?? $target?->notes_md, 'knowers' => $target?->knowers ?? []],
            'investigation' => ['title' => $idea->title, 'question_md' => $idea->notes_md ?? '', 'subjects' => $target?->subjects ?? [], 'evidence' => $target?->evidence ?? [], 'is_resolved' => $target?->is_resolved ?? false],
            'relationship' => ['source_key' => $custom['source_key'] ?? $target?->source_key, 'target_key' => $custom['target_key'] ?? $target?->target_key, 'label' => $idea->title, 'period' => $custom['period'] ?? $target?->period, 'notes_md' => $idea->notes_md, 'is_dependency' => $custom['is_dependency'] ?? $target?->is_dependency ?? false],
        };
        Validator::make($fields, ['aliases' => ['sometimes', 'array', 'max:100'], 'aliases.*' => ['string', 'max:255'], 'roles' => ['sometimes', 'array', 'max:30'], 'roles.*' => ['string', 'max:100'], 'domain' => ['nullable', 'string', 'max:255'], 'measure' => ['sometimes', 'string', 'max:255']])->validate();
        if ($idea->kind === 'entry' && ! WorldEntryType::whereKey($fields['type_id'])->exists()) {
            $this->conflict('Choose an encyclopedia entry type in the idea details before accepting.');
        }
        if ($idea->kind === 'event' && $target?->calendar_id && $idea->date_label !== $target->date_label) {
            $this->conflict('This event uses a calendar date. Change its date in the Timeline tool; freeform sandbox labels cannot replace calendar dates.');
        }
        if ($idea->kind === 'relationship') {
            if (! $fields['source_key'] || ! $fields['target_key'] || $fields['source_key'] === $fields['target_key']) {
                $this->conflict('Choose two distinct established subjects for this relationship, or use a relationship link between board pieces.');
            }
            foreach (['source_key', 'target_key'] as $key) {
                if (! preg_match('/^(person|entry|system):/', $fields[$key])) {
                    $this->conflict('Choose characters, encyclopedia entries or magic systems as relationship subjects.');
                }
            }
            $fields['source_name'] = $this->name($exercise->world, $fields['source_key']);
            $fields['target_name'] = $this->name($exercise->world, $fields['target_key']);
        }

        return $fields;
    }

    private function endpoint(WorldExercise $exercise, int $id, array $resolved): string
    {
        $idea = $exercise->ideas()->findOrFail($id);
        $key = $resolved[$id] ?? $idea->target_key;
        if (! $key) {
            $this->conflict('A connected proposal has not been included in this acceptance.');
        }
        $target = $this->context->target($exercise->world, $key);
        if (! isset($resolved[$id]) && $idea->target_revision !== $target->revision) {
            $this->conflict('A connected established record changed. Refresh its reference before accepting.');
        }

        return $key;
    }

    private function name(World $world, string $key): string
    {
        $target = $this->context->target($world, $key);

        return $target->name ?? $target->title ?? $target->label;
    }

    private function requireNoNewLinks(World $world, string $key, array $changes): void
    {
        $createdKeys = array_column(array_filter($changes, fn ($entry) => isset($entry['key']) && $entry['before'] === null), 'key');
        $relationships = $world->relationships()->where(fn ($query) => $query->where('source_key', $key)->orWhere('target_key', $key))->get();
        foreach ($relationships as $relationship) {
            if (! in_array('relationship:'.$relationship->id, $createdKeys, true)) {
                $this->conflict('New relationships depend on this record. Review them before undoing its creation.');
            }
        }
        if (str_starts_with($key, 'person:')) {
            $person = $this->context->target($world, $key);
            foreach ($person->events as $event) {
                $captured = collect($changes)->contains(fn ($entry) => ($entry['participant']['event'] ?? null) === 'event:'.$event->id && ($entry['participant']['person'] ?? null) === $key);
                if (! $captured) {
                    $this->conflict('New event appearances depend on this character. Review them before undoing its creation.');
                }
            }
        }
    }

    private function conflict(string $message): never
    {
        throw ValidationException::withMessages(['canon' => $message]);
    }
}
