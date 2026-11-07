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
        $validated = Validator::make(is_array($proposal) ? $proposal : [], [
            'intent' => [$structured ? 'required' : 'sometimes', Rule::in(['discuss', 'propose', 'refine', 'clarify'])],
            'reply' => ['required', 'string', 'max:30000'],
            'ideas' => ['present', 'array', 'max:20'],
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
            'links' => ['sometimes', 'array', 'max:40'],
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
        foreach ($validated['ideas'] as &$idea) {
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

    public function prepare(AssistantTurn $turn, array $proposal): array
    {
        foreach ($proposal['ideas'] as &$idea) {
            if (! empty($idea['target_key'])) {
                $target = $this->worldContext->target($turn->exercise->world, $idea['target_key']);
                $revision = $turn->context_manifest['proposal_targets'][$idea['target_key']] ?? collect($turn->context_manifest['records'] ?? [])
                    ->firstWhere('key', $idea['target_key'])['revision'] ?? null;
                if (! str_starts_with($idea['target_key'], $idea['kind'].':') || $revision === null || $target->revision !== (int) $revision) {
                    throw ValidationException::withMessages(['proposals' => 'A proposed update has no current scoped reference. Ask for a refreshed proposal.']);
                }
                $idea['target_revision'] = (int) $revision;
            }
            if (! empty($idea['proposal_id'])) {
                $captured = collect($turn->request_context['sandbox'] ?? [])->firstWhere('id', $idea['proposal_id']);
                $board = $turn->exercise->ideas()->findOrFail($idea['proposal_id']);
                if (
                    ! $captured
                    || ! $board->canEdit()
                    || $board->kind !== $idea['kind']
                    || $board->revision !== (int) ($idea['proposal_revision'] ?? 0)
                    || $board->revision !== (int) $captured['revision']
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
                    $this->worldContext->target($turn->exercise->world, $idea['fields'][$endpoint]);
                }
            }
            $target = ! empty($idea['target_key']) ? $this->worldContext->target($turn->exercise->world, $idea['target_key']) : null;
            ProposalTypes::validateScope($turn->exercise->world, $idea['kind'], $idea['fields'] ?? [], $target);
            if (($idea['completeness'] ?? '') === 'complete' && ProposalTypes::structureProblems($idea['kind'], $idea['fields'] ?? [], $target)) {
                ProposalTypes::invalid('The requested '.$idea['kind'].' is structurally incomplete.');
            }
            foreach ($idea['fields']['beats'] ?? [] as $beat) {
                if (str_starts_with($beat['event_ref'] ?? '', 'board:')) {
                    $eventDraft = $turn->exercise->ideas()->findOrFail((int) substr($beat['event_ref'], 6));
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
            array_walk_recursive($idea['fields'], function (&$content) use ($turn): void {
                if (is_string($content)) {
                    $content = $this->reading->citations($turn, $content);
                }
            });
            $idea['basis'] ??= 'interpretation';
            $idea['evidence'] = $this->reading->citations($turn, $idea['evidence'] ?? 'No supporting evidence supplied. Review before accepting.');
        }
        unset($idea);
        foreach ($proposal['links'] ?? [] as $link) {
            $kinds = [];
            foreach (['source', 'target'] as $side) {
                $endpoint = $link[$side];
                if (is_string($endpoint)) {
                    $this->worldContext->target($turn->exercise->world, $endpoint, true);
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
            World::lockForUpdate()->findOrFail($turn->exercise->world_id);
            $exercise = WorldExercise::lockForUpdate()->findOrFail($turn->exercise_id);
            Gate::forUser($author ?? auth()->user())->authorize('update', $exercise);
            $turn = $exercise->turns()->lockForUpdate()->findOrFail($turn->id);
            abort_unless($turn->status === 'completed' && $turn->proposals, 409);
            abort_unless($exercise->world && ! $exercise->world->trashed(), 409);
            $ideas = [];
            foreach ($turn->proposals['ideas'] ?? [] as $index => $fields) {
                $token = 'turn:'.$turn->id.':'.$index;
                $collectedId = $turn->request_context['collected_proposals'][$index] ?? null;
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
                $coverage = $turn->evidenceLimitations();
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
                    'structure' => ['version' => 3, 'deliverable' => $turn->proposals['deliverable'] ?? 'records', 'completeness' => $fields['completeness'] ?? 'unassessed', 'missing' => $fields['missing'] ?? []],
                    'basis' => $fields['basis'] ?? 'interpretation',
                    'evidence' => $fields['evidence'] ?? '',
                    'turn_id' => $turn->id,
                    'recovery' => collect($turn->request_context['recovery'] ?? [])
                        ->only(['message_id', 'idea_id', 'idea_revision', 'source_turn_id'])
                        ->all(),
                    'previous' => $board?->provenance ? collect($board->provenance)->except('previous')->all() : null,
                ];
                $attributes += ['origin' => 'assistant', 'source_message_id' => $turn->response_id, 'source_token' => $token];
                if ($board) {
                    $board->revise($attributes, $board->revision);
                    $ideas[$index] = $board->refresh();
                } else {
                    $ideas[$index] = $exercise->ideas()
                        ->create($attributes + ['x' => 40 + ($index % 3) * 260, 'y' => 40 + intdiv($index, 3) * 170, 'position' => $index]);
                }
            }
            $settings = $turn->request_context ?? [];
            $settings['collected_proposals'] = array_map(fn ($idea) => $idea->id, $ideas);
            $turn->update(['request_context' => $settings]);
            foreach ($ideas as $idea) {
                $custom = $idea->fields ?? [];
                foreach ($custom['beats'] ?? [] as $index => $beat) {
                    if (str_starts_with($beat['event_ref'] ?? '', 'board:')) {
                        $eventDraft = $turn->exercise->ideas()->findOrFail((int) substr($beat['event_ref'], 6));
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
            foreach ($turn->proposals['links'] ?? [] as $link) {
                $endpoints = [];
                foreach (['source', 'target'] as $side) {
                    $endpoint = $link[$side];
                    $endpoints[$side.'_id'] = is_int($endpoint) ? $ideas[$endpoint]->id : null;
                    $endpoints[$side.'_key'] = is_string($endpoint) ? $endpoint : null;
                }
                $exercise->links()->firstOrCreate($endpoints + ['kind' => $link['kind'], 'label' => $link['label']]);
            }
        });
    }
}
