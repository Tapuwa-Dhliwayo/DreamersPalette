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
    public const FIELDS = [
        'aliases',
        'roles',
        'keep_previous_name',
        'domain',
        'type_id',
        'measure',
        'source_key',
        'target_key',
        'period',
        'is_dependency',
        'left_label',
        'right_label',
        'top_label',
        'bottom_label',
        'criteria',
    ];

    public const KINDS = ['thought', 'question', 'person', 'event', 'relationship', 'entry', 'system', 'arc', 'knowledge', 'investigation', 'name', 'map', 'alignment', 'comparison'];

    public function __construct(private WorldContext $worldContext, private SourceReading $reading) {}

    public function decode(string $content): array
    {
        try {
            $proposal = AssistantProviders::decodeJsonReply($content);
        } catch (\JsonException) {
            throw ValidationException::withMessages(['proposals' => 'The response was not valid proposal JSON. The full response is retained; ask the assistant to try again.']);
        }
        $validated = Validator::make(is_array($proposal) ? $proposal : [], [
            'intent' => ['sometimes', Rule::in(['discuss', 'propose', 'refine', 'clarify'])],
            'reply' => ['required', 'string', 'max:30000'],
            'ideas' => ['present', 'array', 'max:20'],
            'ideas.*.kind' => ['required', Rule::in(self::KINDS)],
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
            'ideas.*.fields' => ['nullable', 'array:'.implode(',', self::FIELDS)],
            'ideas.*.fields.domain' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ideas.*.fields.measure' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ideas.*.fields.type_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'ideas.*.fields.source_key' => ['sometimes', 'nullable', 'string', 'max:80'],
            'ideas.*.fields.target_key' => ['sometimes', 'nullable', 'string', 'max:80'],
            'ideas.*.fields.period' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ideas.*.fields.is_dependency' => ['sometimes', 'boolean'],
            'ideas.*.fields.keep_previous_name' => ['sometimes', 'boolean'],
            'ideas.*.fields.left_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ideas.*.fields.right_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ideas.*.fields.top_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ideas.*.fields.bottom_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ideas.*.fields.aliases' => ['sometimes', 'array', 'max:100'],
            'ideas.*.fields.aliases.*' => ['string', 'max:255'],
            'ideas.*.fields.roles' => ['sometimes', 'array', 'max:30'],
            'ideas.*.fields.roles.*' => ['string', 'max:100'],
            'ideas.*.fields.criteria' => ['sometimes', 'array', 'max:20'],
            'ideas.*.fields.criteria.*' => ['string', 'max:255'],
            'links' => ['sometimes', 'array', 'max:40'],
            'links.*.source' => ['required', 'integer', 'min:0'],
            'links.*.target' => ['required', 'integer', 'min:0'],
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
        foreach ($validated['links'] ?? [] as $link) {
            if ($link['source'] === $link['target'] || ! isset($validated['ideas'][$link['source']], $validated['ideas'][$link['target']])) {
                throw ValidationException::withMessages(['proposals' => 'A proposed connection had an invalid endpoint. The original response is retained.']);
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
            $idea['basis'] ??= 'interpretation';
            $idea['evidence'] = $this->reading->citations($turn, $idea['evidence'] ?? 'No supporting evidence supplied. Review before accepting.');
        }
        unset($idea);

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
                $custom = collect($fields['fields'] ?? [])->only(self::FIELDS)->all();
                $attributes = collect($fields)->only([
                    'kind',
                    'title',
                    'notes_md',
                    'uncertainty',
                    'alternative',
                    'lane',
                    'date_label',
                    'target_key',
                    'target_revision',
                ])->all();
                $attributes['notes_md'] = $fields['notes_md'] ?? $board?->notes_md ?? $base['notes_md'] ?? null;
                $attributes['fields'] = array_replace(
                    collect($base)->only(['aliases', 'roles', 'domain', 'type_id', 'source_key', 'target_key', 'period', 'is_dependency'])->all(),
                    $board?->fields ?? [],
                    $custom,
                );
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
            foreach ($turn->proposals['links'] ?? [] as $link) {
                $exercise->links()->firstOrCreate(['source_id' => $ideas[$link['source']]->id, 'target_id' => $ideas[$link['target']]->id, 'kind' => $link['kind'], 'label' => $link['label']]);
            }
        });
    }
}
