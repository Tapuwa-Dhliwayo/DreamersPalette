<?php

namespace App\Services\Exchange;

use App\Models\ExchangeImport;
use App\Models\Poem;
use App\Models\PoemAnalysisConversation;
use App\Models\PoemAnalysisSnapshot;
use App\Models\User;
use App\Models\World;
use App\Models\WorldExercise;
use App\Services\Worldbuilding\ProposalSource;
use App\Services\Worldbuilding\ProposalTypes;
use App\Services\Worldbuilding\SandboxProposals;
use App\Services\Worldbuilding\WorldContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Turns an uploaded exchange file into reviewable work. No assistant turn, connection,
 * usage row or provider call exists on this path; the file is the whole origin.
 */
class ExchangeImports
{
    private const ALTERNATIVE_LABEL = 'Imported alternative';

    private const ALTERNATIVE_MISSING = 'Imported as an alternative to a record or proposal that changed; compare it with the current version before accepting.';

    public function __construct(private SandboxProposals $proposals, private WorldContext $worldContext) {}

    public function preview(User $author, string $markdown): ExchangeImport
    {
        $package = ExchangePackage::parse($markdown);
        $payload = $package->payload;
        $previous = ExchangeImport::where('author_id', $author->id)->where('package_id', $payload['package_id'])->first();
        if ($previous?->isSaved()) {
            if ($previous->canonical_hash === $package->canonicalHash) {
                return $previous;
            }

            throw ValidationException::withMessages(['package_id' => 'This package id was already imported with different content; issue a new package_id.']);
        }

        $import = new ExchangeImport([
            'author_id' => $author->id,
            'package_id' => $payload['package_id'],
            'parent_package_id' => $payload['parent_package_id'],
            'destination_type' => $package->destinationType(),
            'world_id' => $payload['destination']['world'] ?? null,
            'exercise_id' => $payload['destination']['exercise'] ?? null,
            'poem_id' => $payload['destination']['poem'] ?? null,
            'poem_conversation_id' => $payload['destination']['conversation'] ?? null,
            'format_version' => $payload['version'],
            'title' => $payload['title'],
            'status' => 'previewed',
            'raw_hash' => $package->rawHash,
            'canonical_hash' => $package->canonicalHash,
            'body_md' => $package->body,
            'payload' => $payload,
            'external_origin' => $payload['external_origin'] ?? null,
        ]);
        $summary = match ($import->destination_type) {
            'world_exercise' => $this->previewWorld($author, $import),
            'poem_analysis' => $this->previewPoem($author, $import),
        };
        $import->receipt = ['preview' => $summary];
        $import->raw_path = 'exchange-imports/'.$author->id.'/'.Str::uuid().'.md';
        Storage::disk('local')->put($import->raw_path, $markdown);

        try {
            DB::transaction(function () use ($import, $previous): void {
                $previous?->discard();
                $import->save();
            });
        } catch (\Throwable $failure) {
            Storage::disk('local')->delete($import->raw_path);

            throw $failure;
        }

        return $import;
    }

    public function save(User $author, ExchangeImport $import, bool $conflictsAsAlternatives = false): ExchangeImport
    {
        return DB::transaction(function () use ($author, $import, $conflictsAsAlternatives): ExchangeImport {
            $locked = ExchangeImport::where('author_id', $author->id)->lockForUpdate()->findOrFail($import->id);
            if ($locked->isSaved()) {
                return $locked;
            }
            $receipt = match ($locked->destination_type) {
                'world_exercise' => $this->saveWorld($author, $locked, $conflictsAsAlternatives),
                'poem_analysis' => $this->savePoem($author, $locked),
            };
            $locked->markSaved($receipt);

            return $locked;
        });
    }

    // World

    private function worldDestination(User $author, ExchangeImport $import): array
    {
        $world = World::where('author_id', $author->id)->findOrFail($import->world_id);
        $exercise = $import->exercise_id ? $world->exercises()->findOrFail($import->exercise_id) : null;
        Gate::forUser($author)->authorize('update', $exercise ?? $world);

        return [$world, $exercise];
    }

    private function previewWorld(User $author, ExchangeImport $import): array
    {
        [$world, $exercise] = $this->worldDestination($author, $import);

        return $this->inspectWorld($import, $world, $exercise)['summary'];
    }

    /** Validates the package against the world as it is now; the same call backs the preview and the save. */
    private function inspectWorld(ExchangeImport $import, World $world, ?WorldExercise $exercise): array
    {
        $section = $import->payload['world'];
        [$ideas, $conflicts] = $this->separateConflicts($world, $exercise, $section['ideas']);
        $workingExercise = $exercise ?? new WorldExercise(['title' => $import->title]);
        $workingExercise->world_id = $world->id;
        $workingExercise->setRelation('world', $world);
        try {
            $prepared = $this->proposals->prepareFrom(
                ProposalSource::forImport($import, $workingExercise),
                ['deliverable' => $section['deliverable'], 'ideas' => $ideas, 'links' => $section['links']],
            );
        } catch (ModelNotFoundException) {
            throw ValidationException::withMessages(['world' => 'A record or proposal the package refers to is not available in this world.']);
        } catch (ValidationException $failure) {
            throw ValidationException::withMessages(collect($failure->errors())->mapWithKeys(fn (array $messages, string $key): array => ['world.'.$key => $messages])->all());
        }
        $items = $this->worldItems($world, $exercise, $section['ideas'], $prepared['ideas'], $conflicts);

        return [
            'proposal' => $prepared,
            'conflicts' => $conflicts,
            'summary' => [
                'destination' => 'world_exercise',
                'deliverable' => $section['deliverable'],
                'creates_exercise' => $exercise === null,
                'exercise_title' => $exercise?->title ?? $import->title,
                'items' => $items,
                'link_count' => count($section['links']),
                'conflict_count' => count($conflicts),
                'duplicate_count' => array_sum(array_map(fn (array $item): int => count($item['possible_duplicates']), $items)),
            ],
        ];
    }

    /**
     * Stale references are reported, never thrown, so the author sees all of them at once. Each
     * conflicted idea is rewritten as a new alternative so the rest of the package can still be checked.
     */
    private function separateConflicts(World $world, ?WorldExercise $exercise, array $ideas): array
    {
        $conflicts = [];
        foreach ($ideas as $index => $idea) {
            $found = [];
            $targetLost = false;
            if (! empty($idea['target_key'])) {
                $target = $this->existingTarget($world, $idea, $index);
                $found = match (true) {
                    $target->trashed() => ['target_archived' => 'The record this updates has been archived.'],
                    $target->revision !== (int) $idea['target_revision'] => ['stale_target' => 'The record changed after you read it (revision '.$idea['target_revision'].', now '.$target->revision.').'],
                    default => [],
                };
                $targetLost = $found !== [];
            }
            if (! empty($idea['proposal_id'])) {
                $found += $this->proposalConflict($exercise, $idea, $index);
            }
            if ($found) {
                $conflicts[$index] = $found;
                $ideas[$index] = $this->asAlternative($idea, $targetLost);
            }
        }

        return [$ideas, $conflicts];
    }

    private function existingTarget(World $world, array $idea, int $index)
    {
        try {
            $target = $this->worldContext->target($world, $idea['target_key'], true);
        } catch (ModelNotFoundException|ValidationException) {
            throw ValidationException::withMessages(['world.ideas.'.$index.'.target_key' => 'That record is not available in this world.']);
        }
        if (! str_starts_with($idea['target_key'], $idea['kind'].':')) {
            throw ValidationException::withMessages(['world.ideas.'.$index.'.target_key' => 'The target must be a record of the same kind as the idea.']);
        }

        return $target;
    }

    private function proposalConflict(?WorldExercise $exercise, array $idea, int $index): array
    {
        $board = $exercise?->ideas()->withTrashed()->find($idea['proposal_id']);
        $unavailable = ValidationException::withMessages(['world.ideas.'.$index.'.proposal_id' => 'That proposal is not on this exercise board, or its kind or target differs.']);
        if (! $board || $board->kind !== $idea['kind']) {
            throw $unavailable;
        }
        // Accepted or archived proposals are history: reported before the target is compared, since acceptance links the idea to its record.
        if ($board->canonised_at !== null) {
            return ['proposal_accepted' => 'The proposal this refines was already accepted; accepted history is never edited.'];
        }
        if ($board->trashed()) {
            return ['proposal_archived' => 'The proposal this refines was archived.'];
        }
        if (($idea['target_key'] ?? null) !== $board->target_key) {
            throw $unavailable;
        }

        return $board->revision === (int) $idea['proposal_revision']
            ? []
            : ['proposal_changed' => 'The proposal this refines changed after you read it (revision '.$idea['proposal_revision'].', now '.$board->revision.').'];
    }

    private function asAlternative(array $idea, bool $targetLost): array
    {
        unset($idea['proposal_id'], $idea['proposal_revision']);
        $idea['alternative'] = self::ALTERNATIVE_LABEL;
        if (! $targetLost) {
            return $idea;
        }
        unset($idea['target_key'], $idea['target_revision']);
        foreach (array_keys(ProposalTypes::definition($idea['kind'])['children'] ?? []) as $collection) {
            foreach ($idea['fields'][$collection] ?? [] as $child => $fields) {
                unset($idea['fields'][$collection][$child]['id'], $idea['fields'][$collection][$child]['revision'], $idea['fields'][$collection][$child]['rule_ids']);
            }
        }
        $idea['completeness'] = 'partial';
        $idea['missing'] = [...($idea['missing'] ?? []), self::ALTERNATIVE_MISSING];

        return $idea;
    }

    private function worldItems(World $world, ?WorldExercise $exercise, array $original, array $prepared, array $conflicts): array
    {
        $items = [];
        foreach ($original as $index => $idea) {
            $action = match (true) {
                ! empty($idea['proposal_id']) => 'refine_proposal',
                ! empty($idea['target_key']) => 'update_record',
                default => 'new',
            };
            $items[] = [
                'index' => $index,
                'kind' => $idea['kind'],
                'title' => $idea['title'],
                'action' => $action,
                'structure' => ProposalTypes::summary($idea['kind'], $prepared[$index]['fields'] ?? []),
                'completeness' => $idea['completeness'],
                'missing' => $idea['missing'] ?? [],
                'conflicts' => array_map(fn (string $code, string $message): array => ['code' => $code, 'message' => $message], array_keys($conflicts[$index] ?? []), $conflicts[$index] ?? []),
                'possible_duplicates' => $action === 'new' ? $this->possibleDuplicates($world, $exercise, $idea) : [],
            ];
        }

        return $items;
    }

    /** A warning only: two records may legitimately share a name, so nothing is ever merged. */
    private function possibleDuplicates(World $world, ?WorldExercise $exercise, array $idea): array
    {
        $title = mb_strtolower($idea['title']);
        $duplicates = [];
        $relation = ProposalTypes::definition($idea['kind'])['relation'] ?? null;
        if ($relation) {
            $column = ProposalTypes::definition($idea['kind'])['title'];
            foreach ($world->{$relation}()->whereRaw('LOWER(`'.$column.'`) = ?', [$title])->limit(3)->get() as $record) {
                $duplicates[] = ['type' => 'record', 'key' => $idea['kind'].':'.$record->id, 'title' => $record->{$column}];
            }
        }
        $pending = $exercise?->ideas()->whereNull('canonised_at')->where('kind', $idea['kind'])->whereRaw('LOWER(title) = ?', [$title])->limit(3)->get() ?? [];
        foreach ($pending as $board) {
            $duplicates[] = ['type' => 'proposal', 'id' => $board->id, 'title' => $board->title];
        }

        return $duplicates;
    }

    private function saveWorld(User $author, ExchangeImport $import, bool $conflictsAsAlternatives): array
    {
        World::lockForUpdate()->findOrFail($import->world_id);
        [$world, $exercise] = $this->worldDestination($author, $import);
        if ($exercise) {
            WorldExercise::lockForUpdate()->findOrFail($exercise->id);
        }
        $inspection = $this->inspectWorld($import, $world, $exercise);
        $conflicts = $inspection['conflicts'];
        if ($conflicts && ! $conflictsAsAlternatives) {
            throw ValidationException::withMessages(collect($conflicts)->mapWithKeys(fn (array $found, int $index): array => [
                'world.ideas.'.$index => implode(' ', $found).' Save the changed items as alternatives, or ask the client to re-read and issue a new package.',
            ])->all());
        }
        $exercise ??= $world->exercises()->create(['title' => $import->title, 'purpose' => $import->payload['brief_md'], 'context_keys' => [], 'passages' => []]);
        $message = $exercise->messages()->create(['role' => 'assistant', 'content' => $import->body_md, 'import_id' => $import->id]);
        $import->update(['exercise_id' => $exercise->id]);
        $ideas = $this->proposals->stage(ProposalSource::forImport($import, $exercise), $inspection['proposal'], $author);

        return [
            'exercise_id' => $exercise->id,
            'message_id' => $message->id,
            'ideas' => array_map(fn ($idea): int => $idea->id, $ideas),
            'link_count' => count($inspection['proposal']['links'] ?? []),
            'warnings' => $this->worldWarnings($inspection['summary']['items']),
            'url' => route('studio.world.sandbox', ['world' => $world->id, 'exercise' => $exercise->id], false),
        ];
    }

    private function worldWarnings(array $items): array
    {
        $warnings = [];
        foreach ($items as $item) {
            foreach ($item['conflicts'] as $conflict) {
                $warnings[] = '"'.$item['title'].'" was staged as an alternative: '.$conflict['message'];
            }
            foreach ($item['possible_duplicates'] as $duplicate) {
                $warnings[] = '"'.$item['title'].'" may duplicate an existing '.$duplicate['type'].' named "'.$duplicate['title'].'".';
            }
        }

        return $warnings;
    }

    // Poetry

    private function poemDestination(User $author, ExchangeImport $import): array
    {
        $poem = Poem::with('collection')->where('author_id', $author->id)->findOrFail($import->poem_id);
        $conversation = $import->poem_conversation_id
            ? $poem->analysisConversations()->where('author_id', $author->id)->findOrFail($import->poem_conversation_id)
            : null;
        Gate::forUser($author)->authorize('update', $conversation ?? $poem);

        return [$poem, $conversation];
    }

    private function previewPoem(User $author, ExchangeImport $import): array
    {
        [$poem, $conversation] = $this->poemDestination($author, $import);

        return $this->inspectPoem($author, $import, $poem, $conversation, false)['summary'];
    }

    private function inspectPoem(User $author, ExchangeImport $import, Poem $poem, ?PoemAnalysisConversation $conversation, bool $capture): array
    {
        $section = $import->payload['poetry'];
        [$snapshot, $version] = $this->resolveSnapshot($poem, $section['snapshot']['fingerprint'], 'poetry.snapshot.fingerprint', $capture);
        $comparisons = [];
        foreach ($section['comparisons'] as $index => $comparison) {
            $other = Poem::where('author_id', $author->id)->whereKeyNot($poem->id)->find($comparison['poem']);
            if (! $other) {
                throw ValidationException::withMessages(['poetry.comparisons.'.$index.'.poem' => 'That poem is not one of yours, or it is the poem under analysis.']);
            }
            [$comparisonSnapshot, $comparisonVersion] = $this->resolveSnapshot($other, $comparison['fingerprint'], 'poetry.comparisons.'.$index.'.fingerprint', $capture);
            $comparisons[] = ['poem' => $other->id, 'title' => $other->title, 'snapshot' => $comparisonVersion, 'snapshot_id' => $comparisonSnapshot?->id];
        }
        $notes = $poem->authorNotes()->whereIn('id', $section['author_note_ids'])->get();
        if ($notes->count() !== count($section['author_note_ids'])) {
            throw ValidationException::withMessages(['poetry.author_note_ids' => 'A referenced note is not one of this poem\'s current author notes.']);
        }

        return [
            'snapshot' => $snapshot,
            'comparisons' => $comparisons,
            'summary' => [
                'destination' => 'poem_analysis',
                'poem_title' => $poem->title,
                'snapshot' => $version,
                'newer_version' => $version === 'retained_previous',
                'comparisons' => array_map(fn (array $row): array => array_diff_key($row, ['snapshot_id' => 0]), $comparisons),
                'author_notes' => $notes->map(fn ($note): array => ['id' => $note->id, 'content' => Str::limit($note->content, 160)])->all(),
                'suggested_notes' => count($section['suggested_notes']),
                'creates_conversation' => $conversation === null,
                'conversation_title' => $conversation?->title ?? $import->title,
            ],
        ];
    }

    /** An analysis is only ever attached to the exact text it read; it never silently moves to newer text. */
    private function resolveSnapshot(Poem $poem, string $fingerprint, string $errorKey, bool $capture): array
    {
        if (PoemAnalysisSnapshot::fingerprint($poem) === $fingerprint) {
            return [$capture ? PoemAnalysisSnapshot::capture($poem) : null, 'current'];
        }
        $retained = PoemAnalysisSnapshot::where('poem_id', $poem->id)->where('fingerprint', $fingerprint)->first();
        if (! $retained) {
            throw ValidationException::withMessages([$errorKey => 'The version of "'.$poem->title.'" this analysis read is not retained and is no longer current. Re-read the poem and regenerate the analysis.']);
        }

        return [$retained, 'retained_previous'];
    }

    private function savePoem(User $author, ExchangeImport $import): array
    {
        Poem::lockForUpdate()->findOrFail($import->poem_id);
        [$poem, $conversation] = $this->poemDestination($author, $import);
        $inspection = $this->inspectPoem($author, $import, $poem, $conversation, true);
        if (! $conversation) {
            $conversation = $poem->analysisConversations()->make(['title' => $import->title]);
            $conversation->author_id = $author->id;
            $conversation->save();
        }
        $message = $conversation->messages()->create(['role' => 'assistant', 'content' => $import->body_md, 'import_id' => $import->id]);
        $conversation->touch();
        $import->update(['poem_conversation_id' => $conversation->id, 'snapshot_id' => $inspection['snapshot']->id]);

        return [
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'snapshot_id' => $inspection['snapshot']->id,
            'snapshot' => $inspection['summary']['snapshot'],
            'newer_version' => $inspection['summary']['newer_version'],
            'comparison_snapshot_ids' => array_column($inspection['comparisons'], 'snapshot_id'),
            'warnings' => $inspection['summary']['newer_version'] ? ['This analysis read an earlier version of the poem; the poem has changed since.'] : [],
            'url' => '/dashboard/poems/'.$poem->id.'/analysis',
        ];
    }
}
