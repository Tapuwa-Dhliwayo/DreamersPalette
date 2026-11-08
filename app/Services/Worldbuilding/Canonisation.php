<?php

namespace App\Services\Worldbuilding;

use App\Models\CanonChange;
use App\Models\MagicSystem;
use App\Models\SandboxIdea;
use App\Models\User;
use App\Models\World;
use App\Models\WorldExercise;
use App\Models\WorldMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class Canonisation
{
    public function __construct(private WorldContext $context, private ProposalChildren $children) {}

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
            if (! $idea->canEdit() || ! isset(ProposalTypes::definition($idea->kind)['relation'])) {
                $this->conflict('Choose an editable record proposal. Thoughts, questions and name shortlists remain on the board; assign a name to a character before canonising it.');
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
            foreach ($fields['beats'] ?? [] as $beat) {
                if (! str_starts_with($beat['event_ref'] ?? '', 'board:')) {
                    continue;
                }
                $eventIdea = $exercise->ideas()->findOrFail((int) substr($beat['event_ref'], 6));
                if ($eventIdea->kind !== 'event' || (! $eventIdea->target_key && ! in_array($eventIdea->id, $ids, true))) {
                    $this->conflict('Include the referenced event proposal in this review.');
                }
            }
            $questionsField = $idea->kind === 'investigation' ? 'open_questions_md' : 'notes_md';
            $existingQuestions = array_key_exists($questionsField, $fields)
                ? ($fields[$questionsField] ?? '')
                : ($target?->getAttribute($questionsField) ?? '');
            if (filled($idea->uncertainty) && ! str_contains($existingQuestions, $idea->uncertainty)) {
                $fields[$questionsField] = trim($existingQuestions."\n\n### Open questions\n\n".$idea->uncertainty);
            }
            $changes[] = [
                'idea_id' => $idea->id,
                'idea_revision' => $idea->revision,
                'kind' => $idea->kind,
                'title' => $idea->title,
                'target_key' => $idea->target_key,
                'before' => $target ? $this->context->snapshot($target, true) : null,
                'after' => $fields,
                'provenance' => $idea->provenanceForReview(),
                'uncertainty' => $idea->uncertainty,
                'structure_problems' => ProposalTypes::structureProblems($idea->kind, $idea->fields ?? [], $target),
            ];
        }
        $links = $exercise->links()->where(fn ($query) => $query->whereIn('source_id', $ids)->orWhereIn('target_id', $ids))->orderBy('id')->get()->map(function ($link) use ($exercise, $ids): array {
            $fields = $link->only(['id', 'source_id', 'target_id', 'source_key', 'target_key', 'kind', 'label', 'notes_md', 'revision']);
            $fields['endpoints'] = [];
            foreach (['source', 'target'] as $side) {
                $id = $link->{$side.'_id'};
                $key = $link->{$side.'_key'};
                if ($key) {
                    $target = $this->context->target($exercise->world, $key, true);
                    $fields['endpoints'][] = ['kind' => explode(':', $key)[0], 'position' => $target->position ?? null, 'id' => null, 'title' => $target->name ?? $target->title ?? $target->label, 'revision' => null, 'target_key' => $key, 'target_revision' => $target->revision];

                    continue;
                }
                $endpoint = $exercise->ideas()->findOrFail($id);
                if ($link->kind !== 'connection' && ! in_array($id, $ids, true) && ! $endpoint->canonised_at && ! $endpoint->target_key) {
                    $this->conflict('Include both new ends of the connection in review.');
                }
                $target = $endpoint->target_key ? $this->context->target($exercise->world, $endpoint->target_key) : null;
                $fields['endpoints'][] = ['kind' => $endpoint->kind, 'position' => $endpoint->fields['position'] ?? $endpoint->position, 'id' => $id, 'title' => $endpoint->title, 'revision' => $endpoint->revision, 'target_key' => $endpoint->target_key, 'target_revision' => $target?->revision];
            }

            ProposalTypes::validateLinkKinds($link->kind, array_column($fields['endpoints'], 'kind'));
            if ($link->kind === 'before' && (float) $fields['endpoints'][0]['position'] >= (float) $fields['endpoints'][1]['position']) {
                $this->conflict('A before link conflicts with the proposed event order. Adjust the sequence before review.');
            }

            return $fields;
        })->all();
        $counts = collect($changes)->countBy('kind')->all();
        foreach ($changes as $change) {
            foreach (ProposalTypes::summary($change['kind'], $change['after']) as $field => $count) {
                $counts[$field] = ($counts[$field] ?? 0) + $count;
            }
        }
        $counts['participants'] = count(array_filter($links, fn ($link) => $link['kind'] === 'participant')) + array_sum(array_map(fn ($change) => count($change['after']['person_ids'] ?? []), $changes));
        $review = ['structure_counts' => $counts, 'exercise_id' => $exercise->id, 'ids' => $ids, 'changes' => $changes, 'links' => $links];
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
            foreach ($exercise->ideas()->whereIn('id', array_unique(array_merge($review['ids'] ?? [], array_column($review['links'] ?? [], 'source_id'), array_column($review['links'] ?? [], 'target_id'))))->whereNotNull('target_key')->get() as $reference) {
                $target = $this->context->target($world, $reference->target_key);
                $target->newQuery()->whereKey($target->id)->lockForUpdate()->firstOrFail();
            }
            foreach ($review['links'] ?? [] as $link) {
                foreach ($link['endpoints'] ?? [] as $endpoint) {
                    if (! empty($endpoint['target_key'])) {
                        $target = $this->context->target($world, $endpoint['target_key'], true);
                        $target->newQuery()->withTrashed()->whereKey($target->id)->lockForUpdate()->firstOrFail();
                    }
                }
            }
            $current = $this->review($exercise, $review['ids'] ?? []);
            if (! hash_equals($current['fingerprint'], $review['fingerprint'] ?? '')) {
                $this->conflict('The proposals or their connections changed. Review the latest changes before accepting.');
            }
            $changes = [];
            $resolved = [];
            $author = User::findOrFail($world->author_id);
            $ordered = collect($current['changes'])->sortBy(fn ($change) => match ($change['kind']) {
                'calendar' => 0,'person' => 1,'event' => 2,'arc' => 4,default => 3
            });
            foreach ($ordered as $change) {
                $idea = $exercise->ideas()->findOrFail($change['idea_id']);
                $target = $change['target_key'] ? $this->context->target($world, $change['target_key']) : null;
                $before = $target ? $this->context->snapshot($target, true) : null;
                $attributes = collect($change['after'])->except(array_merge(array_keys(ProposalTypes::definition($idea->kind)['children'] ?? []), ['children_mode', 'person_ids']))->all();
                if (! $target) {
                    $target = $idea->kind === 'system' ? MagicSystem::start($world, $attributes, $author) : $world->{ProposalTypes::definition($idea->kind)['relation']}()->create($attributes)->refresh();
                }
                $this->children->apply($idea->kind, $target, $change['after'], $exercise, $resolved);
                if ($target instanceof MagicSystem) {
                    $target->saveNotes($before ? $attributes : [], $target->revision, $author);
                } elseif ($before) {
                    $target->revise($attributes, $target->revision);
                } elseif (! empty(ProposalTypes::definition($idea->kind)['children']) || isset($change['after']['person_ids'])) {
                    $target->increment('revision');
                }
                $target->refresh();
                if ($target instanceof WorldMap) {
                    $target->captureVersion($author->id);
                }
                $key = $idea->kind.':'.$target->id;
                $resolved[$idea->id] = $key;
                $changes[] = ['key' => $key, 'idea_id' => $idea->id, 'provenance' => $idea->provenanceForReview(), 'before' => $before, 'after' => $this->context->snapshot($target, true)];
                $idea->forceFill(['target_key' => $key, 'target_revision' => $target->revision, 'canonised_at' => now(), 'revision' => $idea->revision + 1])->save();
            }
            foreach ($current['links'] as $link) {
                if ($link['kind'] === 'connection') {
                    continue;
                }
                $source = $this->endpoint($exercise, $link['source_id'], $resolved, $link['source_key'] ?? null);
                $target = $this->endpoint($exercise, $link['target_id'], $resolved, $link['target_key'] ?? null);
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
                    $person = $this->context->target($world, $personKey, true);
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
                    $change['after'] = $this->context->snapshot($this->context->target($world, $change['key']), true) + ['_structured' => true];
                    if ($change['idea_id']) {
                        $exercise->ideas()->whereKey($change['idea_id'])->update(['target_revision' => $change['after']['revision']]);
                    }
                }
            }
            unset($change);
            foreach ($changes as &$entry) {
                if (isset($entry['participant'])) {
                    $entry['after_revision'] = $this->context->target($world, $entry['participant']['event'])->revision;
                }
            }
            unset($entry);

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
                if (isset($entry['after']['_structured']) && $this->context->snapshot($target, true) != collect($entry['after'])->except('_structured')->all()) {
                    $this->conflict('A child record or participant changed after acceptance. Its newer work cannot be overwritten by Undo.');
                }
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
                if (isset($entry['after']['_structured'])) {
                    $this->children->restore(explode(':', $entry['key'])[0], $target, $entry['before'], $this->worldAuthor($world));
                }
                if ($entry['before'] === null) {
                    $target->setArchived(true, auth()->id());
                } elseif ($target instanceof MagicSystem) {
                    $target->saveNotes(collect($entry['before'])->only($target->getFillable())->all(), $target->revision, auth()->user());
                } else {
                    $target->revise(collect($entry['before'])->only($target->getFillable())->all(), $target->revision);
                }
                if ($entry['before'] !== null && $target instanceof WorldMap) {
                    $target->captureVersion(auth()->id());
                }
                if ($entry['idea_id']) {
                    $idea = $exercise->ideas()->findOrFail($entry['idea_id']);
                    $proposalFields = $idea->fields ?? [];
                    if ($entry['before'] !== null) {
                        foreach (ProposalTypes::definition($idea->kind)['children'] ?? [] as $collection => $relation) {
                            foreach ($proposalFields[$collection] ?? [] as $index => $child) {
                                if (empty($child['id'])) {
                                    continue;
                                }
                                $restored = $target->{$relation}()->find($child['id']);
                                if ($restored) {
                                    $proposalFields[$collection][$index]['revision'] = $restored->revision;
                                }
                            }
                        }
                    }
                    $idea->fields = $proposalFields;
                    $idea->forceFill(['canonised_at' => null, 'target_key' => $entry['before'] ? $entry['key'] : null, 'target_revision' => $entry['before'] ? $target->fresh()->revision : null, 'revision' => $idea->revision + 1])->save();
                }
            }
            $change->markReversed();
        });
    }

    private function fields(SandboxIdea $idea, WorldExercise $exercise, ?Model $target): array
    {
        $custom = ProposalTypes::validateFields($idea->kind, $idea->fields ?? []);
        ProposalTypes::validateScope($exercise->world, $idea->kind, $custom, $target);
        $structure = $idea->provenance['structure'] ?? [];
        if (($structure['completeness'] ?? '') === 'unsupported') {
            $this->conflict('This proposal is unsupported. Resolve its missing work before accepting.');
        }
        if (($structure['completeness'] ?? '') === 'complete' && ProposalTypes::structureProblems($idea->kind, $custom, $target)) {
            $this->conflict('This proposal claims completeness but is missing its structure.');
        }
        $definition = ProposalTypes::definition($idea->kind);
        $fields = collect($custom)->only(array_merge($target?->getFillable() ?? $this->newModel($idea->kind, $exercise)->getFillable(), array_keys($definition['children'] ?? []), ['children_mode', 'person_ids']))->all();
        $fields[$definition['title']] = $idea->title;
        if (in_array('notes_md', $target?->getFillable() ?? $this->newModel($idea->kind, $exercise)->getFillable(), true) && ($idea->notes_md !== null || ($idea->provenance['notes_provided'] ?? false))) {
            $fields['notes_md'] = $idea->notes_md;
        }
        if (! $target) {
            if (in_array('notes_md', $this->newModel($idea->kind, $exercise)->getFillable(), true)) {
                $fields += ['notes_md' => ''];
            }
            $fields += match ($idea->kind) {
                'person' => ['aliases' => [], 'roles' => [], 'profile' => []], 'event' => ['position' => $idea->position ?? 0, 'date_label' => $idea->date_label],
                'entry','system' => ['aliases' => []], 'arc' => ['measure' => 'Pressure'],
                'knowledge' => ['truth_md' => '', 'knowers' => []],
                'investigation' => ['question_md' => '', 'subjects' => [], 'evidence' => [], 'is_resolved' => false],
                'map' => ['width_px' => 1200, 'height_px' => 800, 'unit' => 'km', 'pins' => [], 'journey' => []],
                'comparison' => ['subjects' => []], 'calendar' => ['weekdays' => [], 'leap_days' => 1],
                default => [],
            };
        }
        if ($idea->kind === 'person') {
            if (array_key_exists('profile', $fields)) {
                $fields['profile'] = array_replace($target?->profile ?? [], $fields['profile']);
            }
            if ($target && $target->name !== $idea->title && ($custom['keep_previous_name'] ?? false)) {
                $fields['aliases'] = array_values(array_unique([...($fields['aliases'] ?? $target->aliases), $target->name]));
            }
        }
        if ($idea->kind === 'event') {
            $fields['position'] = $custom['position'] ?? $idea->position;
        }
        if ($idea->kind === 'event' && ! isset($custom['date_label']) && $idea->date_label !== null && (! $target || $idea->date_label !== '')) {
            $fields['date_label'] = $idea->date_label;
        }
        if ($idea->kind === 'relationship') {
            $combined = array_replace($target?->attributesToArray() ?? [], $fields);
            if (empty($combined['source_key']) || empty($combined['target_key']) || $combined['source_key'] === $combined['target_key']) {
                $this->conflict('Choose two distinct established relationship subjects.');
            }
            $fields['source_name'] = $this->name($exercise->world, $combined['source_key']);
            $fields['target_name'] = $this->name($exercise->world, $combined['target_key']);
        }
        $combined = array_replace($target?->attributesToArray() ?? [], $fields);
        $required = match ($idea->kind) {
            'entry' => ['type_id'], 'alignment' => ['left_label', 'right_label', 'top_label', 'bottom_label'],
            'comparison' => ['criteria'], 'calendar' => ['months'], 'investigation' => ['question_md'], default => [],
        };
        foreach ($required as $field) {
            if (! filled($combined[$field] ?? null)) {
                $this->conflict('Set '.str_replace('_', ' ', $field).' before accepting this '.$idea->kind.'.');
            }
        }

        return $fields;
    }

    private function worldAuthor(World $world): User
    {
        return User::findOrFail($world->author_id);
    }

    private function newModel(string $kind, WorldExercise $exercise): Model
    {
        return $exercise->world->{ProposalTypes::definition($kind)['relation']}()->make();
    }

    private function endpoint(WorldExercise $exercise, ?int $id, array $resolved, ?string $existingKey = null): string
    {
        if ($existingKey) {
            $this->context->target($exercise->world, $existingKey, true);

            return $existingKey;
        }
        $idea = $exercise->ideas()->findOrFail($id);
        $key = $resolved[$id] ?? $idea->target_key;
        if (! $key) {
            $this->conflict('A connected proposal has not been included in this acceptance.');
        }
        $target = $this->context->target($exercise->world, $key, true);
        if (! isset($resolved[$id]) && $idea->target_revision !== $target->revision) {
            $this->conflict('A connected established record changed. Refresh its reference before accepting.');
        }

        return $key;
    }

    private function name(World $world, string $key): string
    {
        $target = $this->context->target($world, $key, true);

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
        $changedKeys = array_column($changes, 'key');
        [$kind,$id] = explode(':', $key);
        $id = (int) $id;
        if ($kind === 'event') {
            foreach ($world->arcs()->whereHas('beats', fn ($query) => $query->where('event_id', $id))->get() as $arc) {
                if (! in_array('arc:'.$arc->id, $changedKeys, true)) {
                    $this->conflict('New arc beats depend on this event. Review them before Undo.');
                }
            }
            foreach ($world->knowledge()->get() as $knowledge) {
                if (collect($knowledge->knowers ?? [])->contains('event_id', $id) && ! in_array('knowledge:'.$knowledge->id, $changedKeys, true)) {
                    $this->conflict('New knowledge depends on this event.');
                }
            }
        }
        if ($kind === 'calendar' && $world->events()->where('calendar_id', $id)->whereNotIn('id', array_map(fn ($entry) => (int) substr($entry, 6), array_filter($changedKeys, fn ($entry) => str_starts_with($entry, 'event:'))))->exists()) {
            $this->conflict('New events depend on this calendar.');
        }
        if (in_array($kind, ['person', 'entry', 'system'], true)) {
            foreach (['comparisons' => 'subjects', 'investigations' => 'subjects', 'knowledge' => 'knowers'] as $relation => $field) {
                $recordKind = match ($relation) {
                    'comparisons' => 'comparison','investigations' => 'investigation','knowledge' => 'knowledge'
                };
                foreach ($world->{$relation}()->get() as $record) {
                    if (collect($record->{$field} ?? [])->contains('key', $key) && ! in_array($recordKind.':'.$record->id, $changedKeys, true)) {
                        $this->conflict('New structured records depend on this subject.');
                    }
                }
            }
        }
        if (in_array($kind, ['person', 'system'], true)) {
            $column = $kind === 'person' ? 'person_id' : 'system_id';
            foreach ($world->alignmentCharts()->whereHas('placements', fn ($query) => $query->where($column, $id))->get() as $chart) {
                if (! in_array('alignment:'.$chart->id, $changedKeys, true)) {
                    $this->conflict('New placements depend on this subject.');
                }
            }
        }
        if ($kind === 'person') {
            foreach ($world->arcs()->where('person_id', $id)->get() as $arc) {
                if (! in_array('arc:'.$arc->id, $changedKeys, true)) {
                    $this->conflict('New arcs belong to this character.');
                }
            }
        }
        if ($kind === 'entry') {
            foreach ($world->maps()->get() as $map) {
                if (collect($map->pins ?? [])->contains('entry_id', $id) && ! in_array('map:'.$map->id, $changedKeys, true)) {
                    $this->conflict('New map pins depend on this entry.');
                }
            }
        }
        if ($kind === 'system' && $this->context->target($world, $key)->variations()->exists()) {
            $this->conflict('New manuscript variations depend on this system.');
        }
        if (str_starts_with($key, 'person:')) {
            $person = $this->context->target($world, $key);
            foreach ($person->events as $event) {
                $captured = collect($changes)->contains(fn ($entry) => ($entry['participant']['event'] ?? null) === 'event:'.$event->id && ($entry['participant']['person'] ?? null) === $key);
                if (! $captured && ! in_array('event:'.$event->id, $changedKeys, true)) {
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
