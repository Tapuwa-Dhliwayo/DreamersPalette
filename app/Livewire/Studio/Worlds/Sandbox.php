<?php

namespace App\Livewire\Studio\Worlds;

use App\Livewire\Forms\Worlds\ConversationForm;
use App\Livewire\Forms\Worlds\ExerciseForm;
use App\Livewire\Forms\Worlds\NamingConventionForm;
use App\Livewire\Forms\Worlds\SandboxIdeaForm;
use App\Livewire\Forms\Worlds\SandboxLinkForm;
use App\Models\AssistantConnection;
use App\Models\ManuscriptChapter;
use App\Models\NamingConvention;
use App\Models\World;
use App\Models\WorldEntryType;
use App\Models\WorldExercise;
use App\Services\Worldbuilding\AssistantConversation;
use App\Services\Worldbuilding\Canonisation;
use App\Services\Worldbuilding\SandboxProposals;
use App\Services\Worldbuilding\WorldContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

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

    public array $selectedIdeas = [];

    public string $view = 'board';

    public string $panel = 'idea';

    public string $alternativeFilter = '';

    public string $referenceKey = '';

    public string $nameTarget = '';

    public string $notice = '';

    public bool $showArchived = false;

    public function mount(World $world): void
    {
        $this->authorize('view', $world);
        $this->world = $world;
        $this->sendRequest = (string) Str::uuid();
        if ($this->exerciseId) {
            $this->openExercise($this->exerciseId);
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
        $exercise = $this->world->exercises()->findOrFail($id);
        $this->authorize('view', $exercise);
        $this->exerciseId = $id;
        $this->exerciseForm->fill(array_map(fn ($field) => $field ?? '', $exercise->only(['title', 'purpose', 'instructions', 'summary', 'revision'])));
        $this->exerciseForm->context_keys = $exercise->context_keys ?? [];
        $this->exerciseForm->passages = $exercise->passages ?? [];
        $this->exerciseForm->context_keys ??= [];
        $this->exerciseForm->passages ??= [];
        $this->conversationForm->connection_id = $exercise->connection_id;
        $this->reset('ideaId', 'selectedIdeas', 'canonReview', 'undoMove', 'alternativeFilter');
        $this->panel = 'idea';
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
                $exercise->turns()->whereIn('status', ['queued', 'running'])->update(['status' => 'cancelled', 'finished_at' => now()]);
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
            $custom = $idea->fields ?? [];
            $this->ideaForm->fill(collect($custom)->only(['domain', 'type_id', 'measure', 'source_key', 'target_key', 'period', 'is_dependency'])->map(fn ($field, $key) => $field ?? ($key === 'type_id' ? null : ''))->all());
            $this->ideaForm->aliases = implode("\n", $custom['aliases'] ?? []);
            $this->ideaForm->roles = implode(', ', $custom['roles'] ?? []);
        }
        $this->panel = 'idea';
        $this->resetValidation();
    }

    public function saveIdea(): void
    {
        $fields = $this->ideaForm->fields();
        $id = $this->write(function ($exercise) use ($fields): int {
            if ($this->ideaId) {
                $idea = $exercise->ideas()->findOrFail($this->ideaId);
                abort_unless($idea->canEdit(), 409);
                if ($idea->target_key && ! str_starts_with($idea->target_key, $fields['kind'].':')) {
                    throw ValidationException::withMessages(['ideaForm.kind' => 'A linked record must keep its original kind. Fork it for a different kind.']);
                }
                $idea->revise($fields, $this->ideaForm->revision);

                return $idea->id;
            }
            $count = $exercise->ideas()->count();

            return $exercise->ideas()->create($fields + ['x' => 40 + ($count % 3) * 260, 'y' => 40 + intdiv($count, 3) * 170])->id;
        });
        $this->editIdea($id);
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

            return $exercise->ideas()->create(['kind' => in_array($kind, SandboxProposals::KINDS, true) ? $kind : 'thought', 'title' => $record->name ?? $record->title ?? $record->label, 'notes_md' => $record->notes_md ?? $record->truth_md ?? $record->question_md ?? '', 'target_key' => $this->referenceKey, 'target_revision' => $record->revision, 'fields' => collect($record->attributesToArray())->only(['aliases', 'roles', 'domain', 'type_id', 'measure', 'source_key', 'target_key', 'period', 'is_dependency'])->all(), 'position' => $record->position ?? 0, 'date_label' => $record->date_label ?? '', 'x' => 40, 'y' => 40])->id;
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
        if ($this->ideaId === $id) {
            $this->ideaForm->revision = $revision + 1;
        }

        return ['revision' => $revision + 1];
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
        $this->write(function ($exercise) use ($sourceId, $sourceRevision, $targetId, $targetRevision, $after): void {
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
                $idea->revise(['position' => ($index + 1) * 10, 'lane' => $target->lane], $idea->revision);
            }
        });
        if ($this->ideaId) {
            $this->ideaForm->revision = $this->exercise()->ideas()->findOrFail($this->ideaId)->revision;
        }
        $this->notice = 'Sandbox event sequence saved. Established dates are unchanged.';
    }

    public function archiveIdea(int $id, int $revision, bool $archive): void
    {
        $this->write(fn ($exercise) => $exercise->ideas()->withTrashed()->findOrFail($id)->setArchived($archive, auth()->id(), $revision));
        $this->ideaId = null;
        $this->notice = $archive ? 'Idea archived. Its conversation and connections are retained.' : 'Idea restored.';
    }

    public function saveLink(): void
    {
        $fields = $this->linkForm->fields();
        $this->write(function ($exercise) use ($fields): void {
            foreach (['source_id', 'target_id'] as $key) {
                $exercise->ideas()->findOrFail($fields[$key]);
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
        $conversation->send($this->exercise(true), $connection, $fields['content'], $fields['mode'], $this->sendRequest);
        $this->sendRequest = (string) Str::uuid();
        $this->conversationForm->content = '';
        $this->notice = 'Request saved. This turn uses '.$connection->name.'.';
    }

    public function stopTurn(int $id): void
    {
        $exercise = $this->exercise(true);
        $exercise->turns()->whereKey($id)->whereIn('status', ['queued', 'running'])->update(['status' => 'cancelled', 'finished_at' => now()]);
        $this->notice = 'Stopped. Received writing is retained.';
    }

    public function refreshConversation(): void
    {
        $exercise = $this->exercise();
        $exercise->turns()->whereIn('status', ['queued', 'running'])->where('updated_at', '<', now()->subSeconds(config('worldbuilding.timeout') + 60))->update(['status' => 'failed', 'error' => 'This response was interrupted or timed out. Received writing is retained. Send a new request to continue.', 'finished_at' => now()]);
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
            $person = $exercise->ideas()->findOrFail((int) $this->nameTarget);
            abort_unless($name->kind === 'name' && $person->kind === 'person' && $person->canEdit(), 422);
            $person->revise(['title' => $name->title], $person->revision);
        });
        $this->notice = 'Name assigned to the sandbox character. Established names are unchanged.';
    }

    public function reviewCanon(Canonisation $canon): void
    {
        $this->canonReview = $canon->review($this->exercise(true), $this->selectedIdeas);
        $this->canonRequest = (string) Str::uuid();
        $this->panel = 'canon';
    }

    public function acceptCanon(Canonisation $canon): void
    {
        abort_unless($this->canonReview && $this->canonRequest, 409);
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
        $this->notice = 'Naming guidance saved. Only reviewed conventions are sent to the assistant.';
    }

    public function importNamingDrafts(): void
    {
        $this->authorize('update', $this->world);
        $source = 'Pinned ChatGPT chat: Naming Scheme Feedback (6a6f0e2a-6064-83ea-979a-17c59598ea85). Assistant interpretations; not yet verified by the author.';
        foreach ([['Iravain · draft', 'Proposed Greco-Roman influences with fantasy adaptations. Review whether this describes the culture accurately; do not infer a real-world origin for invented names.', 'Tiberius, Tamarius, Samuelá, Romulús, Darohym'], ['Zivarain · draft', 'Proposed Roman/Latin influences with historical and invented adaptations. Social position may affect titles and lineage; avoid assuming common names must be short. Verify this guidance.', 'Aurelia, Marcus, Horatia, Brutus, Sergius, Vertilius, Titus, Graevitius'], ['Gramite · draft', 'Proposed Southern African/Bantu influences, particularly Shona, Ndebele and Zulu. Distinguish real names from fantasy adaptations; verify claimed language origins and meanings. Mirroring a name can be phonetic or symbolic.', 'Uphanti, Nonsi, Nomphi']] as [$name, $guidance, $examples]) {
            $this->conventions()->firstOrCreate(['world_id' => $this->world->id, 'name' => $name], ['user_id' => auth()->id(), 'guidance' => $guidance, 'examples' => $examples, 'source' => $source, 'reviewed' => false]);
        }
        $this->notice = 'Three draft conventions imported from your pinned chat. Review and mark each one before it is used.';
        $this->panel = 'naming';
    }

    public function render(WorldContext $context)
    {
        $this->authorize('view', $this->world);
        $exercise = $this->exerciseId ? $this->exercise() : null;
        $ideas = $exercise?->ideas()->when($this->showArchived, fn ($query) => $query->onlyTrashed())->get() ?? collect();
        $activeIds = $ideas->pluck('id');
        $visible = $ideas->filter(fn ($idea) => $this->alternativeFilter === '' || $idea->alternative === $this->alternativeFilter);
        $links = $exercise?->links()->whereIn('source_id', $activeIds)->whereIn('target_id', $activeIds)->get() ?? collect();
        $catalogue = $context->catalogue($this->world);
        $chapters = ManuscriptChapter::whereHas('manuscript', fn ($query) => $query->where('world_id', $this->world->id)->where('author_id', auth()->id()))->get(['id', 'title']);
        $selected = $this->ideaId ? $exercise?->ideas()->withTrashed()->find($this->ideaId) : null;
        $similar = $selected ? $this->world->referenceCatalogue()->filter(fn ($entry) => mb_strtolower($entry['name']) === mb_strtolower($selected->title) || levenshtein(mb_strtolower($entry['name']), mb_strtolower($selected->title)) <= 2)->pluck('name')->unique() : collect();

        return view('livewire.studio.worlds.sandbox', ['exercise' => $exercise, 'exercises' => $this->world->exercises()->get(), 'archivedExercises' => $this->world->exercises()->onlyTrashed()->get(), 'ideas' => $ideas, 'visibleIdeas' => $visible, 'links' => $links, 'selected' => $selected, 'catalogue' => $catalogue, 'chapters' => $chapters, 'connections' => AssistantConnection::where('user_id', auth()->id())->where('enabled', true)->get(), 'messages' => $exercise?->messages()->get() ?? collect(), 'turns' => $exercise?->turns()->get() ?? collect(), 'changes' => $exercise?->changes()->get() ?? collect(), 'conventions' => $this->conventions()->get(), 'similarNames' => $similar, 'entryTypes' => WorldEntryType::all(), 'kinds' => SandboxProposals::KINDS]);
    }
}
