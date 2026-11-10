<?php

namespace App\Services\Worldbuilding;

use App\Models\AssistantTurn;
use App\Models\User;
use App\Models\World;
use App\Models\WorldEntryType;
use App\Models\WorldExercise;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SandboxProposals
{
    public function __construct(private WorldContext $worldContext, private SourceReading $reading) {}

    public function decode(string $content, bool $structured = false): array
    {
        try {
            $proposal = AssistantProviders::decodeJsonReply($content);
        } catch (\JsonException) {
            throw ValidationException::withMessages(['proposals' => 'The response was not valid proposal JSON. The full response is retained; ask the assistant to try again.']);
        }

        return $this->validate(is_array($proposal) ? $proposal : [], $structured);
    }

    public function validate(array $proposal, bool $structured, int $maxIdeas = 20, int $maxLinks = 40): array
    {
        $validated = Validator::make($proposal, [
            'intent' => [$structured ? 'required' : 'sometimes', Rule::in(['discuss', 'propose', 'refine', 'clarify'])],
            'reply' => ['required', 'string', 'max:30000'],
            'ideas' => ['present', 'array', 'max:'.$maxIdeas],
            'ideas.*.kind' => ['required', Rule::in(ProposalTypes::kinds())],
            'ideas.*.title' => ['required', 'string', 'max:255'],
            'ideas.*.target_key' => ['sometimes', 'nullable', 'string', 'max:80'],
            'ideas.*.proposal_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'ideas.*.proposal_revision' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'ideas.*.basis' => ['sometimes', Rule::in(['textual_evidence', 'interpretation', 'author_decision'])],
            'ideas.*.evidence' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'ideas.*.notes_md' => ['nullable', 'string', 'max:30000'],
            'ideas.*.uncertainty' => ['nullable', 'string', 'max:255'],
            'ideas.*.alternative' => ['nullable', 'string', 'max:255'],
            'ideas.*.lane' => ['nullable', 'string', 'max:255'],
            'ideas.*.date_label' => ['nullable', 'string', 'max:255'],
            'schema_version' => ($structured ? 'required' : 'sometimes').'|integer|in:3',
            'deliverable' => [$structured ? 'required' : 'sometimes', 'in:discussion,records,timeline,arc,character,comparison,alignment,system,knowledge,investigation,map,calendar,naming,mixed'],
            'continuation' => 'sometimes|array:has_more,completed_scope,next_scope',
            'continuation.has_more' => 'required_with:continuation|boolean', 'continuation.completed_scope' => 'required_with:continuation|string|max:2000', 'continuation.next_scope' => 'nullable|string|max:2000',
            'ideas.*.fields' => 'sometimes|array', 'ideas.*.position' => 'sometimes|numeric|between:-99999999999,99999999999',
            'ideas.*.completeness' => [$structured ? 'required' : 'sometimes', 'in:complete,partial,unsupported'],
            'ideas.*.missing' => 'sometimes|array|list|max:30', 'ideas.*.missing.*' => 'string|max:500',
            'links' => ['sometimes', 'array', 'max:'.$maxLinks],
            'links.*.source' => ['required'],
            'links.*.target' => ['required'],
            'links.*.kind' => ['required', Rule::in(['connection', 'before', 'participant', 'relationship', 'depends'])],
            'links.*.label' => ['required', 'string', 'max:255'],
        ])->validate();
        if (in_array($validated['intent'] ?? null, ['discuss', 'clarify'], true) && (! empty($validated['ideas']) || ! empty($validated['links']))) {
            throw ValidationException::withMessages([
                'proposals' => 'The assistant mixed discussion with unapplied changes. Your response is retained; retry to prepare a clear review.',
            ]);
        }
        if (in_array($validated['intent'] ?? null, ['propose', 'refine'], true) && empty($validated['ideas'])) {
            throw ValidationException::withMessages([
                'proposals' => 'The assistant did not return the promised updates. Your question is saved; retry to prepare them.',
            ]);
        }
        foreach ($validated['ideas'] as $index => &$idea) {
            $this->validateIdea($idea, $index);
        }
        unset($idea);
        if (($validated['deliverable'] ?? '') === 'character') {
            foreach ($validated['ideas'] as $draft) {
                if ($draft['kind'] === 'person' && ($draft['completeness'] ?? '') === 'complete' && empty($draft['fields'])) {
                    ProposalTypes::invalid('A character-sheet deliverable needs explicit sheet fields or profile sections. Notes alone do not complete it.');
                }
            }
        }
        $expected = match ($validated['deliverable'] ?? '') {
            'timeline' => 'event','arc' => 'arc','character' => 'person','comparison' => 'comparison','alignment' => 'alignment','system' => 'system','knowledge' => 'knowledge','investigation' => 'investigation','map' => 'map','calendar' => 'calendar','naming' => 'name',default => null
        };
        if ($expected && in_array($validated['intent'] ?? '', ['propose', 'refine'], true) && ! collect($validated['ideas'])->contains('kind', $expected)) {
            ProposalTypes::invalid('The declared '.$validated['deliverable'].' deliverable requires actual '.$expected.' proposals, not a substitute record kind.');
        }
        if (! empty($validated['continuation']['has_more']) && empty($validated['continuation']['next_scope'])) {
            ProposalTypes::invalid('A continued deliverable must state the next scope.');
        }
        foreach ($validated['links'] ?? [] as $link) {
            if (is_string($link['source']) && is_string($link['target'])) {
                ProposalTypes::invalid('A link needs at least one proposed board endpoint; use a relationship proposal for two existing subjects.');
            }
            if ($link['source'] === $link['target']) {
                ProposalTypes::invalid('A connection needs two distinct endpoints.');
            }
            foreach (['source', 'target'] as $side) {
                $endpoint = $link[$side];
                if (is_int($endpoint) && isset($validated['ideas'][$endpoint])) {
                    continue;
                }
                if (is_string($endpoint) && preg_match('/^(person|event|entry|system):[1-9][0-9]*$/', $endpoint)) {
                    continue;
                }
                ProposalTypes::invalid('A connection endpoint must be a proposal index or an existing scoped record key.');
            }
        }

        return $validated;
    }

    /** Errors carry the idea's position so an author or client can see exactly which idea is wrong. */
    private function validateIdea(array &$idea, int $index): void
    {
        try {
            $idea['fields'] = ProposalTypes::validateFields($idea['kind'], $idea['fields'] ?? []);
            if (in_array($idea['completeness'] ?? '', ['partial', 'unsupported'], true) && empty($idea['missing'])) {
                ProposalTypes::invalid('Partial or unsupported proposals must state what is missing.');
            }
            if (($idea['completeness'] ?? '') === 'complete' && ! empty($idea['missing'])) {
                ProposalTypes::invalid('A complete proposal cannot list missing work.');
            }
            if (empty($idea['target_key']) && ($idea['completeness'] ?? '') === 'complete' && ProposalTypes::structureProblems($idea['kind'], $idea['fields'])) {
                ProposalTypes::invalid('A complete '.$idea['kind'].' needs its actual structure, not only Notes.');
            }
        } catch (ValidationException $failure) {
            $located = [];
            foreach ($failure->errors() as $key => $messages) {
                $located[$key === 'structure' ? 'ideas.'.$index : 'ideas.'.$index.'.'.$key] = $messages;
            }

            throw ValidationException::withMessages($located);
        }
    }

    public function prepare(AssistantTurn $turn, array $proposal): array
    {
        return $this->prepareFrom(ProposalSource::forTurn($turn), $proposal);
    }

    public function prepareFrom(ProposalSource $source, array $proposal): array
    {
        $world = $source->exercise->world;
        foreach ($proposal['ideas'] as &$idea) {
            if (! empty($idea['target_key'])) {
                $target = $this->worldContext->target($world, $idea['target_key']);
                $revision = $source->expectedRevision($idea['target_key'], $idea);
                if (! str_starts_with($idea['target_key'], $idea['kind'].':') || $revision === null || $target->revision !== $revision) {
                    throw ValidationException::withMessages(['proposals' => 'A proposed update has no current scoped reference. Ask for a refreshed proposal.']);
                }
                $idea['target_revision'] = $revision;
            }
            if (! empty($idea['proposal_id'])) {
                $capturedRevision = $source->capturedProposalRevision($idea['proposal_id'], $idea);
                $board = $source->exercise->ideas()->findOrFail($idea['proposal_id']);
                if (
                    $capturedRevision === null
                    || ! $board->canEdit()
                    || $board->kind !== $idea['kind']
                    || $board->revision !== (int) ($idea['proposal_revision'] ?? 0)
                    || $board->revision !== $capturedRevision
                    || ($idea['target_key'] ?? null) !== $board->target_key
                ) {
                    throw ValidationException::withMessages(['proposals' => 'The proposal being refined changed. Reopen it and ask again.']);
                }
            }
            if ($idea['kind'] === 'entry' && isset($idea['fields']['type_id']) && ! WorldEntryType::whereKey($idea['fields']['type_id'])->exists()) {
                throw ValidationException::withMessages(['proposals' => 'The assistant returned an unknown encyclopedia entry type.']);
            }
            foreach (['source_key', 'target_key'] as $endpoint) {
                if ($idea['kind'] === 'relationship' && ! empty($idea['fields'][$endpoint])) {
                    $this->worldContext->target($world, $idea['fields'][$endpoint]);
                }
            }
            $target = ! empty($idea['target_key']) ? $this->worldContext->target($world, $idea['target_key']) : null;
            ProposalTypes::validateScope($world, $idea['kind'], $idea['fields'] ?? [], $target);
            if (($idea['completeness'] ?? '') === 'complete' && ProposalTypes::structureProblems($idea['kind'], $idea['fields'] ?? [], $target)) {
                ProposalTypes::invalid('The requested '.$idea['kind'].' is structurally incomplete.');
            }
            foreach ($idea['fields']['beats'] ?? [] as $beat) {
                if (str_starts_with($beat['event_ref'] ?? '', 'board:')) {
                    $eventDraft = $source->exercise->ideas()->findOrFail((int) substr($beat['event_ref'], 6));
                    if ($eventDraft->kind !== 'event') {
                        ProposalTypes::invalid('A beat must link to an event draft.');
                    }
                }
                if (str_starts_with($beat['event_ref'] ?? '', 'idea:')) {
                    $index = (int) substr($beat['event_ref'], 5);
                    if (($proposal['ideas'][$index]['kind'] ?? null) !== 'event') {
                        ProposalTypes::invalid('A beat must reference an event proposal.');
                    }
                }
            }
            array_walk_recursive($idea['fields'], function (&$content) use ($source): void {
                if (is_string($content)) {
                    $content = $source->citations($this->reading, $content);
                }
            });
            $idea['basis'] ??= 'interpretation';
            $idea['evidence'] = $source->citations($this->reading, $idea['evidence'] ?? 'No supporting evidence supplied. Review before accepting.');
        }
        unset($idea);
        foreach ($proposal['links'] ?? [] as $link) {
            $kinds = [];
            foreach (['source', 'target'] as $side) {
                $endpoint = $link[$side];
                if (is_string($endpoint)) {
                    $this->worldContext->target($world, $endpoint, true);
                }
                $kinds[] = is_string($endpoint) ? explode(':', $endpoint)[0] : $proposal['ideas'][$endpoint]['kind'];
            }
            ProposalTypes::validateLinkKinds($link['kind'], $kinds);
        }

        return $proposal;
    }

    public function collect(AssistantTurn $turn, ?User $author = null): void
    {
        DB::transaction(function () use ($turn, $author): void {
            $exercise = $this->lockedExercise($turn->exercise, $author);
            $turn = $exercise->turns()->lockForUpdate()->findOrFail($turn->id);
            abort_unless($turn->status === 'completed' && $turn->proposals, 409);
            $this->stage(ProposalSource::forTurn($turn), $turn->proposals, $author);
        });
    }

    /** Locks and authorises the exercise's world before anything is written to its board. */
    private function lockedExercise(WorldExercise $exercise, ?User $author): WorldExercise
    {
        World::lockForUpdate()->findOrFail($exercise->world_id);
        $locked = WorldExercise::lockForUpdate()->findOrFail($exercise->id);
        Gate::forUser($author ?? auth()->user())->authorize('update', $locked);
        abort_unless($locked->world && ! $locked->world->trashed(), 409);

        return $locked;
    }

    /** Puts a prepared batch on the sandbox board; returns the board ideas keyed by their position in the batch. */
    public function stage(ProposalSource $source, array $proposal, ?User $author = null): array
    {
        return DB::transaction(function () use ($source, $proposal, $author): array {
            $exercise = $this->lockedExercise($source->exercise, $author);
            $ideas = [];
            foreach ($proposal['ideas'] ?? [] as $index => $fields) {
                $token = $source->tokenPrefix().':'.$index;
                $collectedId = $source->collectedIdeaIds()[$index] ?? null;
                $existing = $collectedId
                    ? $exercise->ideas()->withTrashed()->findOrFail($collectedId)
                    : $exercise->ideas()->withTrashed()->where('source_token', $token)->first();
                if ($existing) {
                    if ($existing->trashed()) {
                        throw ValidationException::withMessages(['proposals' => 'A collected proposal was archived. Restore it from the board.']);
                    }
                    $ideas[$index] = $existing;

                    continue;
                }
                $base = [];
                if (! empty($fields['target_key'])) {
                    $target = $this->worldContext->target($exercise->world, $fields['target_key']);
                    if ($target->revision !== (int) ($fields['target_revision'] ?? 0)) {
                        throw ValidationException::withMessages(['proposals' => 'An encyclopedia record changed. Ask for a fresh proposal before collecting.']);
                    }
                    $base = $this->worldContext->snapshot($target);
                }
                $board = ! empty($fields['proposal_id']) ? $exercise->ideas()->lockForUpdate()->findOrFail($fields['proposal_id']) : null;
                if ($board && (! $board->canEdit() || $board->revision !== (int) $fields['proposal_revision'])) {
                    throw ValidationException::withMessages(['proposals' => 'A board proposal changed before collection. Ask for a fresh refinement.']);
                }
                $custom = ProposalTypes::validateFields($fields['kind'], $fields['fields'] ?? []);
                $attributes = collect($fields)->only([
                    'kind',
                    'title',
                    'notes_md',
                    'uncertainty',
                    'alternative',
                    'lane',
                    'date_label',
                    'position',
                    'target_key',
                    'target_revision',
                ])->all();
                if ($fields['kind'] === 'event') {
                    $attributes['position'] = $fields['fields']['position'] ?? $fields['position'] ?? $board?->position ?? $base['position'] ?? $index;
                }
                $attributes['notes_md'] = array_key_exists('notes_md', $fields) ? $fields['notes_md'] : ($board?->notes_md ?? $base['notes_md'] ?? null);
                // Omitted fields are unchanged. Target values are resolved at review, not copied as accidental edits.
                $attributes['fields'] = array_replace($board?->fields ?? [], $custom);
                $coverage = $source->coverage();
                $previousCoverage = ($board?->provenanceForReview() ?? [])['coverage_status'] ?? [];
                if (($previousCoverage['recorded'] ?? null) === false) {
                    $coverage['recorded'] = false;
                }
                if ($previousCoverage['partial'] ?? false) {
                    $coverage['partial'] = true;
                    $coverage['previous_limitations'] = collect($previousCoverage)
                        ->only(['partial', 'scope', 'stop_reason', 'omitted_notes_count'])
                        ->all();
                }
                $attributes['provenance'] = [
                    'coverage_status' => $coverage,
                    'notes_provided' => array_key_exists('notes_md', $fields),
                    'structure' => ['version' => 3, 'deliverable' => $proposal['deliverable'] ?? 'records', 'completeness' => $fields['completeness'] ?? 'unassessed', 'missing' => $fields['missing'] ?? []],
                    'basis' => $fields['basis'] ?? 'interpretation',
                    'evidence' => $fields['evidence'] ?? '',
                    ...$source->provenanceExtras(),
                    'previous' => $board?->provenance ? collect($board->provenance)->except('previous')->all() : null,
                ];
                $attributes += ['origin' => $source->origin(), 'source_message_id' => $source->sourceMessageId(), 'source_token' => $token];
                if ($board) {
                    $board->revise($attributes, $board->revision);
                    $ideas[$index] = $board->refresh();
                } else {
                    $ideas[$index] = $exercise->ideas()
                        ->create($attributes + ['x' => 40 + ($index % 3) * 260, 'y' => 40 + intdiv($index, 3) * 170, 'position' => $index]);
                }
            }
            $source->recordCollected(array_map(fn ($idea) => $idea->id, $ideas));
            foreach ($ideas as $idea) {
                $custom = $idea->fields ?? [];
                foreach ($custom['beats'] ?? [] as $index => $beat) {
                    if (str_starts_with($beat['event_ref'] ?? '', 'board:')) {
                        $eventDraft = $exercise->ideas()->findOrFail((int) substr($beat['event_ref'], 6));
                        if ($eventDraft->kind !== 'event') {
                            ProposalTypes::invalid('A beat must link to an event draft.');
                        }
                    }
                    if (str_starts_with($beat['event_ref'] ?? '', 'idea:')) {
                        $proposalIndex = (int) substr($beat['event_ref'], 5);
                        if (! isset($ideas[$proposalIndex])) {
                            ProposalTypes::invalid('A beat references an unavailable proposal.');
                        }
                        $custom['beats'][$index]['event_ref'] = 'board:'.$ideas[$proposalIndex]->id;
                    }
                }
                if ($custom !== $idea->fields) {
                    $idea->update(['fields' => $custom]);
                }
            }
            foreach ($proposal['links'] ?? [] as $link) {
                $endpoints = [];
                foreach (['source', 'target'] as $side) {
                    $endpoint = $link[$side];
                    $endpoints[$side.'_id'] = is_int($endpoint) ? $ideas[$endpoint]->id : null;
                    $endpoints[$side.'_key'] = is_string($endpoint) ? $endpoint : null;
                }
                $exercise->links()->firstOrCreate($endpoints + ['kind' => $link['kind'], 'label' => $link['label']]);
            }

            return $ideas;
        });
    }
}
