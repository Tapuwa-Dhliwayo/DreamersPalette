<?php

namespace App\Livewire\Studio\Worlds;

use App\Livewire\Forms\Worlds\ConversationForm;
use App\Livewire\Forms\Worlds\ExerciseForm;
use App\Livewire\Forms\Worlds\NamingConventionForm;
use App\Livewire\Forms\Worlds\SandboxIdeaForm;
use App\Livewire\Forms\Worlds\SandboxLinkForm;
use App\Models\AssistantConnection;
use App\Models\AssistantSource;
use App\Models\ManuscriptChapter;
use App\Models\NamingConvention;
use App\Models\World;
use App\Models\WorldEntryType;
use App\Models\WorldExercise;
use App\Services\Worldbuilding\AssistantConversation;
use App\Services\Worldbuilding\AssistantModels;
use App\Services\Worldbuilding\AssistantProviders;
use App\Services\Worldbuilding\AssistantUsage;
use App\Services\Worldbuilding\Canonisation;
use App\Services\Worldbuilding\ProposalTypes;
use App\Services\Worldbuilding\SandboxProposals;
use App\Services\Worldbuilding\WorldContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Ramsey\Uuid\Uuid;

#[Layout('layouts.studio')]
class Sandbox extends Component
{
    public World $world;

    public ExerciseForm $exerciseForm;

    public SandboxIdeaForm $ideaForm;

    public ConversationForm $conversationForm;

    public SandboxLinkForm $linkForm;

    public NamingConventionForm $namingForm;

    #[Url(as: 'exercise')]
    public ?int $exerciseId = null;

    #[Url(as: 'reading')]
    public ?int $readingId = null;

    #[Url(as: 'from')]
    public int $readingFrom = 1;

    #[Url(as: 'to')]
    public int $readingTo = 12;

    #[Locked]
    public ?int $ideaId = null;

    #[Locked]
    public ?int $conventionId = null;

    #[Locked]
    public array $canonReview = [];

    #[Locked]
    public string $canonRequest = '';

    #[Locked]
    public string $sendRequest = '';

    #[Locked]
    public array $undoMove = [];

    #[Locked]
    public array $undoTidy = [];

    public array $selectedIdeas = [];

    public bool $acknowledgeUncertainty = false;

    // One working surface at a time; the conversation is the main surface in Discuss and a drawer otherwise.
    private const VIEWS = ['discuss', 'board', 'timeline', 'list'];

    public string $view = 'discuss';

    // Details open only for a selection or a chosen action; an empty string keeps them closed.
    public string $panel = '';

    public string $alternativeFilter = '';

    public string $referenceKey = '';

    public string $nameTarget = '';

    public string $notice = '';

    public bool $showArchived = false;

    public function mount(World $world): void
    {
        $this->authorize('update', $world);
        $this->world = $world;
        $this->sendRequest = (string) Str::uuid();
        if ($this->exerciseId) {
            $this->openExercise($this->exerciseId);
            if ($this->readingId) {
                $this->openSource($this->readingId, $this->readingFrom, $this->readingTo);
            }
        } elseif (request()->integer('nameFor')) {
            $this->beginNaming(request()->integer('nameFor'));
        }
    }

    private function exercise(bool $write = false): WorldExercise
    {
        $exercise = $this->world->exercises()->findOrFail($this->exerciseId);
        $this->authorize($write ? 'update' : 'view', $exercise);

        return $exercise;
    }

    private function write(callable $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            $world = World::lockForUpdate()->findOrFail($this->world->id);
            $this->authorize('update', $world);

            return $callback($this->exercise(true));
        });
    }

    private function beginNaming(int $personId): void
    {
        $this->authorize('update', $this->world);
        $person = $this->world->people()->findOrFail($personId);
        DB::transaction(function () use ($person): void {
            $world = World::lockForUpdate()->findOrFail($this->world->id);
            $this->authorize('update', $world);
            $exercise = $world->exercises()->firstOrCreate(['title' => 'Names for '.$person->name], ['purpose' => 'Explore names for this character without changing their established name.', 'context_keys' => ['person:'.$person->id], 'passages' => []]);
            $this->openExercise($exercise->id);
            $this->referenceKey = 'person:'.$person->id;
            $this->reference(app(WorldContext::class));
        });
        $this->conversationForm->content = 'Suggest names for '.$person->name.'. Use the reviewed cultural conventions, keep origins honest, and explain how the candidates fit.';
    }

    public function newExercise(): void
    {
        $this->authorize('update', $this->world);
        $this->exerciseId = null;
        $this->ideaId = null;
        $this->exerciseForm->reset();
        $this->panel = 'exercise';
        $this->canonReview = [];
    }

    public function openExercise(int $id): void
    {
        if ($this->exerciseId !== $id) {
            $this->readingId = null;
        }
        $exercise = $this->world->exercises()->findOrFail($id);
        $this->authorize('view', $exercise);
        $this->exerciseId = $id;
        $this->exerciseForm->fill(array_map(fn ($field) => $field ?? '', $exercise->only(['title', 'purpose', 'instructions', 'summary', 'revision'])));
        $this->exerciseForm->context_keys = $exercise->context_keys ?? [];
        $this->exerciseForm->passages = $exercise->passages ?? [];
        $this->exerciseForm->context_keys ??= [];
        $this->exerciseForm->passages ??= [];
        $this->modelCatalogue = ['models' => [], 'status' => 'loading', 'message' => 'Loading models…'];
        $this->conversationForm->connection_id = $exercise->connection_id;
        // Turns come newest first; the prompt and manifest columns are large and not needed here.
        $turns = $exercise->turns()->get(['id', 'model', 'mode', 'reading_depth', 'request_context']);
        $lastTurn = $turns->first();
        $lastReadingTurn = $turns->first(fn ($turn) => empty($turn->request_context['recovery']) && $turn->mode !== 'summary');
        $this->conversationForm->model = $lastTurn?->model ?? '';
        $this->conversationForm->effort = $lastTurn?->request_context['effort'] ?? 'default';
        $this->conversationForm->reading_depth = $lastReadingTurn?->reading_depth ?? 'focused';
        $this->conversationForm->auto_continue = $lastReadingTurn?->request_context['auto_continue'] ?? true;
        $this->reset('ideaId', 'selectedIdeas', 'canonReview', 'undoMove', 'undoTidy', 'alternativeFilter');
        $rememberedView = session('sandbox.view.'.$id);
        $this->view = in_array($rememberedView, self::VIEWS, true) ? $rememberedView : 'discuss';
        $this->panel = '';
        $this->resetValidation();
    }

    public function closeDetails(): void
    {
        $this->panel = '';
        $this->ideaId = null;
        $this->ideaForm->reset();
        $this->proposalTarget = '';
        $this->resetValidation();
    }

    public function saveExercise(WorldContext $context): void
    {
        $this->authorize('update', $this->world);
        $fields = $this->exerciseForm->fields();
        foreach ($fields['context_keys'] as $key) {
            $context->target($this->world, $key);
        }
        foreach ($fields['passages'] as $id) {
            ManuscriptChapter::whereKey($id)->whereHas('manuscript', fn ($query) => $query->where('world_id', $this->world->id)->where('author_id', auth()->id()))->firstOrFail();
        }
        DB::transaction(function () use ($fields): void {
            $world = World::lockForUpdate()->findOrFail($this->world->id);
            $this->authorize('update', $world);
            if ($this->exerciseId) {
                $this->exercise(true)->revise($fields, $this->exerciseForm->revision);
            } else {
                $this->exerciseId = $world->exercises()->create($fields)->id;
            }
        });
        $this->openExercise($this->exerciseId);
        $this->dispatch('sandbox-saved');
        $this->notice = 'Exercise and selected context saved.';
    }

    public function archiveExercise(int $id, int $revision, bool $archive): void
    {
        $this->authorize('update', $this->world);
        DB::transaction(function () use ($id, $revision, $archive): void {
            $world = World::lockForUpdate()->findOrFail($this->world->id);
            $this->authorize('update', $world);
            $exercise = $world->exercises()->withTrashed()->findOrFail($id);
            $exercise->setArchived($archive, auth()->id(), $revision);
            if ($archive) {
                $exercise->turns()->active()->update(['status' => 'cancelled', 'finished_at' => now()]);
            }
        });
        if ($archive) {
            $this->newExercise();
        }
        $this->notice = $archive ? 'Exercise archived. Restore it from Archived exercises.' : 'Exercise restored.';
    }

    public function editIdea(?int $id = null): void
    {
        $exercise = $this->exercise();
        $this->ideaForm->reset();
        $this->proposalTarget = '';
        $this->ideaId = $id;
        if ($id) {
            $idea = $exercise->ideas()->withTrashed()->findOrFail($id);
            $fields = $idea->only(['kind', 'title', 'notes_md', 'uncertainty', 'alternative', 'lane', 'position', 'date_label', 'revision']);
            foreach ($fields as $key => $field) {
                if ($field === null) {
                    $fields[$key] = '';
                }
            }
            $this->ideaForm->fill($fields);
            $target = $idea->target_key ? app(WorldContext::class)->target($this->world, $idea->target_key) : null;
            $custom = array_replace($target ? ProposalTypes::referenceFields($idea->kind, $target) : [], $idea->fields ?? []);
            $this->ideaForm->structured = $custom;
            $structure = $idea->provenance['structure'] ?? [];
            $this->ideaForm->completeness = $structure['completeness'] ?? 'partial';
            $this->ideaForm->missing = implode("\n", $structure['missing'] ?? ['Structure was not assessed for this earlier proposal.']);
            $this->ideaForm->fill(collect($custom)->only(['domain', 'type_id', 'measure', 'source_key', 'target_key', 'period', 'keep_previous_name', 'is_dependency', 'left_label', 'right_label', 'top_label', 'bottom_label'])->map(fn ($field, $key) => $field ?? ($key === 'type_id' ? null : ($key === 'is_dependency' || $key === 'keep_previous_name' ? false : '')))->all());
            $this->ideaForm->aliases = implode("\n", $custom['aliases'] ?? []);
            $this->ideaForm->criteria = implode("\n", $custom['criteria'] ?? []);
            $this->ideaForm->roles = implode(', ', $custom['roles'] ?? []);
        }
        $this->panel = 'idea';
        $this->resetValidation();
    }

    public string $proposalTarget = '';

    public function linkProposal(WorldContext $context): void
    {
        $this->write(function ($exercise) use ($context): void {
            $idea = $exercise->ideas()->lockForUpdate()->findOrFail($this->ideaId);
            abort_unless($idea->canEdit() && ! $idea->target_key, 409);
            if ($idea->revision !== $this->ideaForm->revision || ! str_starts_with($this->proposalTarget, $idea->kind.':')) {
                throw ValidationException::withMessages(['proposalTarget' => 'Reopen this proposal and choose a matching current record.']);
            }
            $record = $context->target($exercise->world, $this->proposalTarget);
            $idea->revise(['target_key' => $this->proposalTarget, 'target_revision' => $record->revision], $idea->revision);
        });
        $this->editIdea($this->ideaId);
        $this->notice = 'Proposal linked. Compare all changes in Review canon before accepting.';
    }

    public function addStructureRow(string $collection): void
    {
        $this->exercise(true);
        $this->ideaForm->addStructureRow($collection);
    }

    public function removeStructureRow(string $collection, int $index): void
    {
        $this->exercise(true);
        $this->ideaForm->removeStructureRow($collection, $index);
    }

    public function saveIdea(): void
    {
        $fields = $this->ideaForm->fields();
        $structure = $fields['structure'];
        unset($fields['structure']);
        $id = $this->write(function ($exercise) use ($fields, $structure): int {
            if ($this->ideaId) {
                $idea = $exercise->ideas()->findOrFail($this->ideaId);
                abort_unless($idea->canEdit(), 409);
                if ($idea->target_key && ! str_starts_with($idea->target_key, $fields['kind'].':')) {
                    throw ValidationException::withMessages(['ideaForm.kind' => 'A linked record must keep its original kind. Fork it for a different kind.']);
                }
                $fields['provenance'] = array_replace($idea->provenance ?? [], ['structure' => $structure]);
                $idea->revise($fields, $this->ideaForm->revision);

                return $idea->id;
            }
            $count = $exercise->ideas()->count();

            return $exercise->ideas()->create($fields + ['provenance' => ['structure' => $structure]] + ['x' => 40 + ($count % 3) * 260, 'y' => 40 + intdiv($count, 3) * 170])->id;
        });
        $this->editIdea($id);
        $this->dispatch('sandbox-saved');
        $this->notice = 'Idea saved in the sandbox. Canon is unchanged.';
    }

    public function reference(WorldContext $context): void
    {
        $record = $context->target($this->world, $this->referenceKey);
        $kind = explode(':', $this->referenceKey)[0];
        $id = $this->write(function ($exercise) use ($record, $kind): int {
            $existing = $exercise->ideas()->where('target_key', $this->referenceKey)->whereNull('canonised_at')->first();
            if ($existing) {
                return $existing->id;
            }

            return $exercise->ideas()->create([
                'kind' => in_array($kind, ProposalTypes::kinds(), true) ? $kind : 'thought',
                'title' => $record->name ?? $record->title ?? $record->label,
                'notes_md' => array_key_exists('notes_md', $record->getAttributes()) ? $record->notes_md : '',
                'target_key' => $this->referenceKey,
                'target_revision' => $record->revision,
                'fields' => ProposalTypes::referenceFields($kind, $record),
                'position' => $record->position ?? 0,
                'date_label' => $record->date_label ?? '',
                'x' => 40,
                'y' => 40,
            ])->id;
        });
        $this->editIdea($id);
        $this->notice = 'Established record referenced. Edits here remain proposals until reviewed.';
    }

    public function refreshReference(WorldContext $context): void
    {
        $id = $this->ideaId;
        $this->write(function ($exercise) use ($context, $id): void {
            $idea = $exercise->ideas()->findOrFail($id);
            abort_unless($idea->target_key && $idea->canEdit(), 409);
            $target = $context->target($this->world, $idea->target_key);
            $idea->revise(['target_revision' => $target->revision], $this->ideaForm->revision);
        });
        $this->editIdea($id);
        $this->notice = 'Reference revision refreshed; your proposed content is retained. Compare it with the current record in the canon review.';
    }

    public function forkIdea(int $id): void
    {
        $new = $this->write(function ($exercise) use ($id): int {
            $idea = $exercise->ideas()->findOrFail($id);
            $fields = $idea->only($idea->getFillable());
            unset($fields['source_token']);
            $fields['alternative'] = ($idea->alternative ? $idea->alternative.' · ' : '').'Alternative';
            $fields['x'] += 35;
            $fields['y'] += 35;
            $fields['origin'] = 'author';

            return $exercise->ideas()->create($fields)->id;
        });
        $this->editIdea($new);
        $this->notice = 'Alternative created. Change its scenario label and details independently.';
    }

    public function moveIdea(int $id, int $revision, int $x, int $y): array
    {
        if ($x < 0 || $x > 10000 || $y < 0 || $y > 10000) {
            throw ValidationException::withMessages(['board' => 'Keep ideas within the board.']);
        }
        $this->write(function ($exercise) use ($id, $revision, $x, $y): void {
            $idea = $exercise->ideas()->findOrFail($id);
            $this->undoMove = ['id' => $id, 'revision' => $revision + 1, 'x' => $idea->x, 'y' => $idea->y];
            // Layout remains editable after canonisation; it never changes the accepted proposal content.
            $idea->revise(['x' => $x, 'y' => $y], $revision);
        });
        if ($this->ideaId === $id && $this->ideaForm->revision === $revision) {
            $this->ideaForm->revision = $revision + 1;
        }

        return ['revision' => $revision + 1];
    }

    public function tidyBoard(bool $compactCards, int $columns): void
    {
        $this->undoTidy = $this->write(fn ($exercise) => $exercise->tidyBoard($compactCards, $columns));
        $this->undoMove = [];
        $this->syncOpenIdeaRevision($this->undoTidy);
        $this->notice = $this->undoTidy ? 'Board tidied. Undo tidy restores the previous layout.' : 'The board is already tidy.';
    }

    public function undoTidyBoard(): void
    {
        if (! $this->undoTidy) {
            return;
        }
        $previous = $this->undoTidy;
        $this->write(fn ($exercise) => $exercise->restoreBoard($previous));
        $this->syncOpenIdeaRevision(array_map(fn (array $saved): array => ['revision' => $saved['revision'] + 1], $previous));
        $this->undoTidy = [];
        $this->notice = 'Previous board layout restored.';
    }

    // Layout moves bump revisions; keep an open idea form saveable afterwards.
    private function syncOpenIdeaRevision(array $moved): void
    {
        if ($this->ideaId && isset($moved[$this->ideaId])) {
            $this->ideaForm->revision = $moved[$this->ideaId]['revision'];
        }
    }

    public function undoMovement(): void
    {
        if ($this->undoMove) {
            $move = $this->undoMove;
            $this->moveIdea($move['id'], $move['revision'], $move['x'], $move['y']);
            $this->undoMove = [];
        }
    }

    public function moveEvent(int $sourceId, int $sourceRevision, int $targetId, int $targetRevision, bool $after = false): void
    {
        $editorRevision = $this->ideaForm->revision;
        $selectedWasCurrent = false;
        $this->write(function ($exercise) use ($sourceId, $sourceRevision, $targetId, $targetRevision, $after, $editorRevision, &$selectedWasCurrent): void {
            $source = $exercise->ideas()->lockForUpdate()->findOrFail($sourceId);
            $target = $exercise->ideas()->lockForUpdate()->findOrFail($targetId);
            abort_unless($source->kind === 'event' && $target->kind === 'event' && $source->canEdit() && $target->canEdit(), 422);
            if ($source->revision !== $sourceRevision || $target->revision !== $targetRevision) {
                throw ValidationException::withMessages(['timeline' => 'These events changed. Refresh the timeline before moving them.']);
            }
            if ($source->alternative !== $target->alternative) {
                throw ValidationException::withMessages(['timeline' => 'Move events within the same scenario. Fork an event to explore a different scenario.']);
            }
            if ($sourceId === $targetId) {
                return;
            }
            $ordered = $exercise->ideas()->where('kind', 'event')->where('alternative', $target->alternative)->where('lane', $target->lane)->whereNull('canonised_at')->lockForUpdate()->get()->reject(fn ($idea) => $idea->id === $sourceId)->values();
            $index = $ordered->search(fn ($idea) => $idea->id === $targetId);
            $ordered->splice($index + ($after ? 1 : 0), 0, [$source]);
            foreach ($ordered as $index => $idea) {
                if ($idea->id === $this->ideaId && $idea->revision === $editorRevision) {
                    $selectedWasCurrent = true;
                }
                $idea->revise(['position' => ($index + 1) * 10, 'lane' => $target->lane], $idea->revision);
            }
        });
        if ($this->ideaId && $selectedWasCurrent) {
            $this->ideaForm->revision = $this->exercise()->ideas()->findOrFail($this->ideaId)->revision;
        }
        $this->notice = 'Sandbox event sequence saved. Established dates are unchanged.';
    }

    public function archiveIdea(int $id, int $revision, bool $archive): void
    {
        $this->write(fn ($exercise) => $exercise->ideas()->withTrashed()->findOrFail($id)->setArchived($archive, auth()->id(), $revision));
        $this->closeDetails();
        $this->notice = $archive ? 'Idea archived. Its conversation and connections are retained.' : 'Idea restored.';
    }

    public function saveLink(): void
    {
        $fields = $this->linkForm->fields();
        $this->write(function ($exercise) use ($fields): void {
            foreach (['source_id', 'target_id'] as $key) {
                if ($fields[$key]) {
                    $exercise->ideas()->findOrFail($fields[$key]);
                } else {
                    app(WorldContext::class)->target($this->world, $fields[str_replace('_id', '_key', $key)]);
                }
            }
            $exercise->links()->firstOrCreate(collect($fields)->except('notes_md')->all(), ['notes_md' => $fields['notes_md']]);
        });
        $this->linkForm->reset();
        $this->notice = 'Sandbox connection saved.';
    }

    public function removeLink(int $id, int $revision): void
    {
        $this->write(function ($exercise) use ($id, $revision): void {
            $link = $exercise->links()->lockForUpdate()->findOrFail($id);
            abort_unless($link->revision === $revision, 409);
            $link->delete();
        });
    }

    public function send(AssistantConversation $conversation): void
    {
        $fields = $this->conversationForm->fields();
        $connection = AssistantConnection::where('user_id', auth()->id())->findOrFail($fields['connection_id']);
        $conversation->send($this->exercise(true), $connection, $fields['content'], 'conversation', $this->sendRequest, $fields['reading_depth'], $fields);
        $this->sendRequest = (string) Str::uuid();
        $this->conversationForm->content = '';
        $this->notice = 'Question saved. Reading continues in the background; Studio activity will show when your answer is ready or needs attention.';
    }

    public function summariseDiscussion(AssistantConversation $conversation): void
    {
        $connection = AssistantConnection::where('user_id', auth()->id())->findOrFail($this->conversationForm->connection_id);
        $conversation->send(
            $this->exercise(true),
            $connection,
            'Prepare a working summary of this discussion for me to review.',
            'summary',
            $this->sendRequest,
            'focused',
            $this->conversationForm->only(['model', 'effort']),
        );
        $this->sendRequest = (string) Str::uuid();
        $this->notice = 'Preparing a discussion summary. Your message and manuscript are unchanged.';
    }

    public function prepareMessageUpdates(int $id, AssistantConversation $conversation): void
    {
        $this->prepareSavedUpdates($conversation, messageId: $id);
    }

    public function prepareIdeaUpdates(int $id, AssistantConversation $conversation): void
    {
        $this->prepareSavedUpdates($conversation, ideaId: $id);
    }

    private function prepareSavedUpdates(AssistantConversation $conversation, ?int $messageId = null, ?int $ideaId = null): void
    {
        $connection = AssistantConnection::where('user_id', auth()->id())->findOrFail($this->conversationForm->connection_id);
        $turn = $conversation->recover($this->exercise(true), $connection, $messageId, $ideaId, $this->conversationForm->only(['model', 'effort']));
        $this->dispatch('sandbox-conversation-focus');
        $this->notice = match ($turn->status) {
            'completed' => 'These updates are prepared. Use Review updates beside that response.',
            'failed', 'paused', 'cancelled' => 'The saved preparation needs attention. Open its response to retry without resending the original question.',
            'queued', 'running' => 'Preparing linked drafts from the saved answer. The novel will not be reread; nothing is saved to canon without review.',
            default => throw new \RuntimeException('Unknown preparation state.'),
        };
    }

    public function restructureSavedTurn(int $id, AssistantConversation $conversation): void
    {
        $exercise = $this->exercise(true);
        $source = $exercise->turns()->findOrFail($id);
        abort_unless($source->status === 'completed' && $source->response_id, 409);
        $connection = AssistantConnection::where('user_id', $this->world->author_id)->findOrFail($source->connection_id);
        if (! $connection->enabled) {
            throw ValidationException::withMessages(['conversationForm.connection_id' => 'Enable the original connection before restructuring this saved output.']);
        }
        $conversation->recover($exercise, $connection, $source->response_id, null, ['model' => $source->model, 'effort' => $source->request_context['effort'] ?? 'default']);
        $this->notice = 'Preparing a structured revision from the saved output. Existing accepted records and Notes stay unchanged until you review new proposals.';
    }

    public function continueStructuredTurn(int $id, AssistantConversation $conversation): void
    {
        $exercise = $this->exercise(true);
        $turn = $exercise->turns()->findOrFail($id);
        abort_unless($turn->status === 'completed' && ($turn->proposals['continuation']['has_more'] ?? false), 409);
        $connection = AssistantConnection::where('user_id', $this->world->author_id)->findOrFail($turn->connection_id);
        $scope = $turn->proposals['continuation'];
        $content = 'Continue the '.($turn->proposals['deliverable'] ?? 'structured').' draft for this original request: '.$turn->question()."\nCompleted scope: ".$scope['completed_scope']."\nNext scope: ".$scope['next_scope']."\nReuse existing and staged records; do not duplicate previous items. Mark remaining work explicitly.";
        $uuid = (string) Uuid::uuid5(Uuid::NAMESPACE_URL, 'dreamers:structured-continuation:'.$turn->id);
        $conversation->send($exercise, $connection, $content, 'conversation', $uuid, $turn->reading_depth, ['model' => $turn->model, 'effort' => $turn->request_context['effort'] ?? 'default']);
        $this->notice = 'Continuation saved. Existing drafts are retained; review each batch before acceptance.';
    }

    public function reviewTurn(int $id, SandboxProposals $proposals, Canonisation $canon): void
    {
        $exercise = $this->exercise(true);
        $turn = $exercise->turns()->findOrFail($id);
        $proposals->collect($turn);
        $drafts = $exercise->ideas()->whereIn('id', $turn->fresh()->request_context['collected_proposals'] ?? [])->get();
        $this->selectedIdeas = $drafts
            ->filter(fn ($idea) => $idea->canEdit() && ! in_array($idea->kind, ['thought', 'question', 'name'], true))
            ->pluck('id')
            ->all();
        if (! $this->selectedIdeas) {
            $this->panel = 'history';
            $this->notice = 'There are no pending record updates in this response. Accepted changes remain in History.';

            return;
        }
        $this->reviewCanon($canon);
    }

    public function editSavedQuestion(int $id): void
    {
        $turn = $this->exercise()->turns()->findOrFail($id);
        $this->conversationForm->content = $turn->question();
        $this->conversationForm->model = $turn->model;
        $this->conversationForm->effort = $turn->request_context['effort'] ?? 'default';
        $this->conversationForm->auto_continue = $turn->request_context['auto_continue'] ?? true;
        $this->conversationForm->reading_depth = $turn->reading_depth;
        $this->conversationForm->connection_id = $turn->connection_id;
        $this->dispatch('sandbox-conversation-focus');
        $this->notice = 'Saved question restored to the composer. You can edit it or save a different source collection before sending.';
    }

    public function retryTurn(int $id, AssistantConversation $conversation): void
    {
        $conversation->retry($this->exercise(true)->turns()->findOrFail($id), $this->conversationForm->auto_continue);
        $this->notice = 'Resuming your saved question. Completed reading notes are retained.';
    }

    public function updatedView(): void
    {
        if (! in_array($this->view, self::VIEWS, true)) {
            $this->view = 'discuss';
        }
        if ($this->exerciseId) {
            session()->put('sandbox.view.'.$this->exerciseId, $this->view);
        }
    }

    #[Locked]
    public array $modelCatalogue = ['models' => [], 'status' => 'loading', 'message' => 'Loading models…'];

    public function loadConnectionModels(bool $refresh = false): void
    {
        $this->authorize('update', $this->world);
        if (! $this->conversationForm->connection_id) {
            $this->modelCatalogue = ['models' => [], 'status' => 'no_connection', 'message' => 'Choose a connection to see its models.'];

            return;
        }
        $connection = AssistantConnection::where('user_id', auth()->id())->where('enabled', true)->findOrFail($this->conversationForm->connection_id);
        $this->modelCatalogue = app(AssistantModels::class)->catalogue($connection, $refresh);
    }

    public function updatedConversationFormModel(): void
    {
        $this->conversationForm->effort = 'default';
    }

    public function updatedConversationFormConnectionId(): void
    {
        $this->conversationForm->model = '';
        $this->conversationForm->effort = 'default';
        $this->loadConnectionModels();
    }

    public function openSource(int $id, int $from = 1, int $to = 12): void
    {
        $exercise = $this->exercise();
        $source = AssistantSource::whereHas('turn', fn ($query) => $query->where('exercise_id', $exercise->id))->findOrFail($id);
        $this->readingId = $source->id;
        $this->readingFrom = max(1, min($from, max(1, count($source->passages))));
        $this->readingTo = max($this->readingFrom, min($to, count($source->passages), $this->readingFrom + 29));
        $this->panel = 'source';
    }

    public function stopTurn(int $id): void
    {
        $exercise = $this->exercise(true);
        $exercise->turns()->find($id)?->stop();
        $this->notice = 'Stopped. Received writing is retained.';
    }

    public function refreshConversation(): void
    {
        $exercise = $this->exercise(true);
        AssistantTurn::expireStalled($exercise->turns()->getQuery());
    }

    public function acceptSummary(int $id): void
    {
        $editorWasCurrent = false;
        $this->write(function ($exercise) use ($id, &$editorWasCurrent): void {
            $editorWasCurrent = $this->exerciseForm->revision === $exercise->revision;
            $turn = $exercise->turns()->findOrFail($id);
            abort_unless($turn->status === 'completed' && $turn->mode === 'summary', 409);
            $message = $exercise->messages()->findOrFail($turn->response_id);
            if (mb_strlen($message->content) > 20000) {
                throw ValidationException::withMessages(['summary' => 'Ask for a shorter summary before using it as working context.']);
            }
            if (($exercise->summary_through_id ?? 0) > $turn->response_id) {
                throw ValidationException::withMessages(['summary' => 'A newer summary is already in use.']);
            }
            $exercise->revise(['summary' => $message->content, 'summary_through_id' => $turn->response_id], $exercise->revision);
        });
        $exercise = $this->exercise();
        if ($editorWasCurrent) {
            $this->exerciseForm->summary = $exercise->summary;
            $this->exerciseForm->revision = $exercise->revision;
        }
        $this->notice = 'Working summary accepted. Covered messages remain saved and visible; future requests use the summary plus newer messages.';
    }

    public function collectTurn(int $id, SandboxProposals $proposals): void
    {
        $exercise = $this->exercise(true);
        $turn = $exercise->turns()->findOrFail($id);
        abort_unless($turn->status === 'completed' && $turn->proposals, 409);
        $proposals->collect($turn);
        $this->notice = 'Proposals collected. Collecting again reuses the same pieces.';
    }

    public function messageToBoard(int $id): void
    {
        $this->write(function ($exercise) use ($id): void {
            $message = $exercise->messages()->findOrFail($id);
            $exercise->ideas()->firstOrCreate(['source_token' => 'message:'.$id], ['kind' => 'thought', 'title' => Str::limit($message->content, 90), 'notes_md' => $message->content, 'source_message_id' => $id, 'origin' => $message->role === 'user' ? 'author' : 'assistant']);
        });
        $this->notice = 'Message placed on the board as an unclassified thought.';
    }

    public function discussIdea(int $id): void
    {
        $idea = $this->exercise()->ideas()->findOrFail($id);
        $this->conversationForm->content = 'Let’s explore “'.$idea->title.'” (sandbox idea '.$idea->id.'). '.$idea->notes_md;
        $this->dispatch('sandbox-conversation-focus');
    }

    public function assignName(): void
    {
        $this->write(function ($exercise): void {
            $name = $exercise->ideas()->findOrFail($this->ideaId);
            $selection = explode(':', $this->nameTarget);
            $person = $exercise->ideas()->findOrFail((int) ($selection[0] ?? 0));
            if ($person->revision !== (int) ($selection[1] ?? 0)) {
                throw ValidationException::withMessages(['nameTarget' => 'The character changed. Choose its current version again before assigning a name.']);
            }
            abort_unless($name->kind === 'name' && $person->kind === 'person' && $person->canEdit(), 422);
            $person->revise(['title' => $name->title], $person->revision);
        });
        $this->notice = 'Name assigned to the sandbox character. Established names are unchanged.';
    }

    public function reviewCanon(Canonisation $canon): void
    {
        $this->acknowledgeUncertainty = false;
        $this->canonReview = $canon->review($this->exercise(true), $this->selectedIdeas);
        $this->canonRequest = (string) Str::uuid();
        $this->panel = 'canon';
    }

    public function acceptCanon(Canonisation $canon): void
    {
        abort_unless($this->canonReview && $this->canonRequest, 409);
        if (
            collect($this->canonReview['changes'])->contains(fn ($change) => filled($change['uncertainty'] ?? null))
            && ! $this->acknowledgeUncertainty
        ) {
            throw ValidationException::withMessages([
                'acknowledgeUncertainty' => 'Confirm that open questions should be kept in the saved Notes, or edit those drafts before accepting.',
            ]);
        }
        $canon->accept($this->exercise(true), $this->canonReview, $this->canonRequest);
        $this->canonReview = [];
        $this->selectedIdeas = [];
        $this->ideaId = null;
        $this->notice = 'Selected changes are now canon. Their source exercise and accepted history are retained.';
        $this->panel = 'history';
    }

    public function reverseCanon(int $id, Canonisation $canon): void
    {
        $canon->reverse($this->exercise(true), $id);
        $this->notice = 'Acceptance reversed. Created records were archived; newer edits are never overwritten.';
    }

    public function editConvention(?int $id = null): void
    {
        $this->authorize('update', $this->world);
        $this->namingForm->reset();
        $this->conventionId = $id;
        if ($id) {
            $record = $this->conventions()->findOrFail($id);
            $this->namingForm->fill(array_map(fn ($field) => $field ?? '', $record->only(['name', 'guidance', 'examples', 'source', 'reviewed', 'revision'])));
            $this->namingForm->personal = $record->world_id === null;
        }
        $this->panel = 'naming';
    }

    private function conventions()
    {
        return NamingConvention::where('user_id', auth()->id())->where(fn ($query) => $query->where('world_id', $this->world->id)->orWhereNull('world_id'));
    }

    public function saveConvention(): void
    {
        $this->authorize('update', $this->world);
        $fields = $this->namingForm->fields();
        DB::transaction(function () use ($fields): void {
            $world = World::lockForUpdate()->findOrFail($this->world->id);
            $this->authorize('update', $world);
            if ($this->conventionId) {
                $record = $this->conventions()->lockForUpdate()->findOrFail($this->conventionId);
                $record->revise($fields, $this->namingForm->revision);
            } else {
                $record = new NamingConvention($fields);
                $record->user_id = auth()->id();
                $record->world_id = $this->namingForm->personal ? null : $this->world->id;
                $record->save();
            }
        });
        $this->editConvention();
        $this->dispatch('sandbox-saved');
        $this->notice = 'Naming guidance saved. Only reviewed conventions are sent to the assistant.';
    }

    public function importNamingDrafts(): void
    {
        abort_unless(AssistantProviders::allowsLocal(auth()->id()), 403);
        $this->authorize('update', $this->world);
        $source = 'Pinned ChatGPT chat: Naming Scheme Feedback (6a6f0e2a-6064-83ea-979a-17c59598ea85). Assistant interpretations; not yet verified by the author.';
        DB::transaction(function () use ($source): void {
            $world = World::lockForUpdate()->findOrFail($this->world->id);
            $this->authorize('update', $world);
            foreach ([['Iravain · draft', 'Proposed Greco-Roman influences with fantasy adaptations. Review whether this describes the culture accurately; do not infer a real-world origin for invented names.', 'Tiberius, Tamarius, Samuelá, Romulús, Darohym'], ['Zivarain · draft', 'Proposed Roman/Latin influences with historical and invented adaptations. Social position may affect titles and lineage; avoid assuming common names must be short. Verify this guidance.', 'Aurelia, Marcus, Horatia, Brutus, Sergius, Vertilius, Titus, Graevitius'], ['Gramite · draft', 'Proposed Southern African/Bantu influences, particularly Shona, Ndebele and Zulu. Distinguish real names from fantasy adaptations; verify claimed language origins and meanings. Mirroring a name can be phonetic or symbolic.', 'Uphanti, Nonsi, Nomphi']] as [$name, $guidance, $examples]) {
                if (! $this->conventions()->where('world_id', $this->world->id)->where('name', $name)->exists()) {
                    $convention = new NamingConvention(['name' => $name, 'guidance' => $guidance, 'examples' => $examples, 'source' => $source, 'reviewed' => false]);
                    $convention->user_id = auth()->id();
                    $convention->world_id = $this->world->id;
                    $convention->save();
                }
            }
        });
        $this->notice = 'Three draft conventions imported from your pinned chat. Review and mark each one before it is used.';
        $this->panel = 'naming';
    }

    public function render(WorldContext $context)
    {
        $this->authorize('update', $this->world);
        $exercise = $this->exerciseId ? $this->exercise() : null;
        $turns = $exercise?->turns()->get() ?? collect();
        $recoveries = $turns->filter(fn ($turn) => $turn->status === 'completed'
            && ! empty($turn->proposals['ideas'])
            && ! empty($turn->request_context['recovery']));
        $recoveredMessages = $recoveries->groupBy(fn ($turn) => $turn->request_context['recovery']['message_id'] ?? 0)
            ->map(fn ($group) => $group->first());
        $recoveredIdeas = $recoveries->groupBy(fn ($turn) => $turn->request_context['recovery']['idea_id'] ?? 0)->map(fn ($group) => $group->first());
        $allIdeas = $exercise?->ideas()->withTrashed()->get() ?? collect();
        $ideas = $allIdeas->filter(fn ($idea) => $idea->trashed() === $this->showArchived);
        $activeIds = $ideas->pluck('id');
        $visible = $ideas->filter(fn ($idea) => $this->alternativeFilter === '' || $idea->alternative === $this->alternativeFilter);
        $links = $exercise?->links()
            ->where(fn ($query) => $query->whereIn('source_id', $activeIds)->orWhereNotNull('source_key'))
            ->where(fn ($query) => $query->whereIn('target_id', $activeIds)->orWhereNotNull('target_key'))
            ->get() ?? collect();
        $catalogue = $context->catalogue($this->world, true);
        $chapters = ManuscriptChapter::whereHas('manuscript', fn ($query) => $query->where('world_id', $this->world->id)->where('author_id', auth()->id()))->with('manuscript:id,title')->orderBy('manuscript_id')->orderBy('position')->get(['id', 'manuscript_id', 'position', 'title']);
        $selected = $this->ideaId ? $exercise?->ideas()->withTrashed()->find($this->ideaId) : null;
        $similar = $selected ? $this->world->referenceCatalogue()->filter(fn ($entry) => mb_strtolower($entry['name']) === mb_strtolower($selected->title) || levenshtein(mb_strtolower($entry['name']), mb_strtolower($selected->title)) <= 2)->pluck('name')->unique() : collect();

        $source = $exercise && $this->readingId ? AssistantSource::whereHas('turn', fn ($query) => $query->where('exercise_id', $exercise->id))->findOrFail($this->readingId) : null;
        $readingSources = $exercise ? AssistantSource::whereHas('turn', fn ($query) => $query->where('exercise_id', $exercise->id))->get()->groupBy('turn_id') : collect();
        foreach ($turns as $sourceTurn) {
            if ($sourceTurn->request_context['recovery'] ?? null) {
                $readingSources->put($sourceTurn->id, $sourceTurn->citationSources());
            }
        }
        $sourceRevisions = ManuscriptChapter::whereIn('id', $readingSources->flatten()->pluck('chapter_id'))->pluck('revision', 'id');

        return view('livewire.studio.worlds.sandbox', [
            'usageTotals' => app(AssistantUsage::class)->summary(auth()->id(), $exercise?->id),
            'source' => $source,
            'readingSources' => $readingSources,
            'sourceRevisions' => $sourceRevisions,
            'exercise' => $exercise,
            'exercises' => $this->world->exercises()->get(),
            'archivedExercises' => $this->world->exercises()->onlyTrashed()->get(),
            'ideas' => $ideas,
            'allIdeas' => $allIdeas,
            'visibleIdeas' => $visible,
            'links' => $links,
            'selected' => $selected,
            'recordUrl' => $selected?->target_key ? $context->url($this->world, $selected->target_key) : null,
            'catalogue' => $catalogue,
            'chapters' => $chapters,
            'connections' => AssistantConnection::where('user_id', auth()->id())->where('enabled', true)->get(),
            'messages' => $exercise?->messages()->get() ?? collect(),
            'turns' => $turns,
            'recoveredMessages' => $recoveredMessages,
            'recoveredIdeas' => $recoveredIdeas,
            'changes' => $exercise?->changes()->get() ?? collect(),
            'conventions' => $this->conventions()->get(),
            'similarNames' => $similar,
            'entryTypes' => WorldEntryType::all(),
            'kinds' => ProposalTypes::kinds(),
            'structureEvents' => $exercise?->ideas()->where('kind', 'event')->get() ?? collect(),
            'structureManuscripts' => $this->world->manuscripts()->get(['id', 'title']),
            'structureCalendars' => $this->world->calendars()->get(['id', 'name']),
            'structureChapters' => ManuscriptChapter::whereHas('manuscript', fn ($query) => $query->where('world_id', $this->world->id)->where('author_id', $this->world->author_id))->get(['id', 'title']),
            'existingRules' => $selected?->kind === 'system' && $selected?->target_key ? $context->target($this->world, $selected->target_key)->rules()->get() : collect(),
        ]);
    }
}
