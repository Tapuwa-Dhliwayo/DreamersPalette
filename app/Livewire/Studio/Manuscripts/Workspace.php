<?php

namespace App\Livewire\Studio\Manuscripts;

use App\Livewire\Forms\Studio\AttachWorldForm;
use App\Livewire\Forms\Studio\ChapterForm;
use App\Livewire\Forms\Studio\FinishDraftForm;
use App\Livewire\Forms\Studio\ReferenceForm;
use App\Livewire\Forms\Worlds\EntryForm;
use App\Models\ChapterReference;
use App\Models\Manuscript;
use App\Models\World;
use App\Models\WorldEntryType;
use App\Services\ManuscriptDraftService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.studio')]
class Workspace extends Component
{
    public Manuscript $manuscript;

    public ReferenceForm $referenceForm;

    public AttachWorldForm $attachWorldForm;

    public bool $linkingWorld = false;

    public EntryForm $entryForm;

    public bool $referencesOpen = false;

    public string $referenceSearch = '';

    public string $inspectedReference = '';

    public bool $creatingReference = false;

    public string $referenceNotice = '';

    #[Locked]
    public string $entryRequest = '';

    public ChapterForm $form;

    public FinishDraftForm $finishForm;

    #[Url(as: 'chapter')]
    public ?int $chapterId = null;

    public string $tab = 'write';

    public bool $finishing = false;

    public ?int $selectedDraftId = null;

    public bool $readingDraft = false;

    public bool $archivedChapters = false;

    public bool $archivedReferences = false;

    public function mount(Manuscript $manuscript): void
    {
        $this->authorize('view', $manuscript);
        $this->manuscript = $manuscript;
        $this->chapterId ??= $manuscript->chapters()->value('id');
        if ($this->chapterId) {
            $manuscript->chapters()->whereKey($this->chapterId)->firstOrFail();
        }
    }

    public function beginWorldLink(): void
    {
        $this->authorize('update', $this->manuscript);
        $this->authorize('viewAny', World::class);
        $this->manuscript->refresh();
        $this->attachWorldForm->reset();
        $this->attachWorldForm->revision = $this->manuscript->revision;
        $this->attachWorldForm->draft_number = $this->manuscript->working_draft_number;
        $this->linkingWorld = true;
    }

    public function attachWorld(): void
    {
        $this->authorize('update', $this->manuscript);
        $selection = $this->attachWorldForm->selection();
        $world = World::where('author_id', Auth::id())->findOrFail($selection['world_id']);
        $this->authorize('view', $world);
        $this->manuscript->linkWorld($world, $selection['revision'], $selection['draft_number']);
        $this->manuscript->refresh();
        $this->linkingWorld = false;
        $this->dispatch('studio-reference-updated', revision: $this->manuscript->revision, catalogue: $world->referenceCatalogue()->all());
    }

    private function referenceWorld(): ?World
    {
        $world = $this->manuscript->world;

        return $world && Auth::user()->can('view', $world) ? $world : null;
    }

    public function openReferences(?array $selection = null): void
    {
        $this->authorize('view', $this->manuscript);
        $this->authorize('view', $this->manuscript->world ?? abort(422, 'Link a world before using references.'));
        $this->resetValidation();
        $this->referenceForm->reset();
        $this->entryForm->reset();
        $this->creatingReference = false;
        $this->referenceNotice = '';
        $this->archivedReferences = false;
        $this->entryRequest = (string) Str::uuid();
        if ($selection) {
            $this->referenceForm->fill($selection);
            $this->referenceForm->selection();
        }
        $this->referenceSearch = $selection['wording'] ?? '';
        $this->inspectedReference = '';
        $this->referencesOpen = true;
    }

    public function inspectReference(string $key): void
    {
        $this->authorize('view', $this->manuscript);
        $this->authorize('view', $this->manuscript->world);
        [$kind, $id] = array_pad(explode(':', $key, 2), 2, 0);
        $this->manuscript->world->referenceTarget($kind, (int) $id, true);
        $this->inspectedReference = $key;
    }

    public function linkReference(string $key, bool $alias = false): void
    {
        $this->authorize('update', $this->manuscript);
        $world = $this->manuscript->world ?? abort(422);
        $this->authorize($alias ? 'update' : 'view', $world);
        $fields = $this->referenceForm->selection();
        [$kind, $id] = array_pad(explode(':', $key, 2), 2, 0);
        $target = $world->referenceTarget($kind, (int) $id);
        $world->getConnection()->transaction(function () use ($fields, $kind, $target, $alias): void {
            ChapterReference::attach($this->manuscript, [...$fields, 'target_kind' => $kind, 'target_id' => $target->id], Auth::user());
            if (! $alias) {
                return;
            }
            $aliases = $this->referenceForm->aliasesFor($kind, $target->aliases, $target->name);
            if ($aliases === $target->aliases) {
                return;
            }
            $writing = ['aliases' => $aliases];
            if ($kind === 'system') {
                $target->saveNotes($writing, $target->revision, Auth::user());

                return;
            }
            $target->revise($writing, $target->revision);
        });
        $this->referenceNotice = $alias ? 'Alias added and passage linked' : 'Passage linked';
        $this->manuscript->refresh();
        $this->dispatch('studio-reference-updated', revision: $this->manuscript->revision, catalogue: $world->referenceCatalogue()->all());
    }

    public function archiveReference(int $id, bool $archive): void
    {
        $this->authorize('update', $this->manuscript);
        $this->manuscript->getConnection()->transaction(function () use ($id, $archive): void {
            $manuscript = Manuscript::whereKey($this->manuscript->id)->lockForUpdate()->firstOrFail();
            $reference = ChapterReference::withTrashed()->where('manuscript_id', $manuscript->id)->where('chapter_id', $this->chapterId)->findOrFail($id);
            $changed = $reference->trashed() !== $archive;
            $reference->setArchived($archive, Auth::id());
            if ($changed) {
                $manuscript->increment('revision');
            }
        });
        $this->manuscript->refresh();
        $this->referenceNotice = $archive ? 'Link archived' : 'Link restored';
        $this->dispatch('studio-reference-updated', revision: $this->manuscript->revision, catalogue: $this->referenceWorld()?->referenceCatalogue()->all() ?? []);
    }

    public function beginReferenceEntry(): void
    {
        $this->authorize('update', $this->manuscript->world);
        $this->entryForm->name = mb_substr($this->referenceForm->wording, 0, 255);
        $this->creatingReference = true;
    }

    public function createReferenceEntry(): void
    {
        $this->authorize('update', $this->manuscript);
        $world = $this->manuscript->world;
        $this->authorize('update', $world);
        abort_unless(Str::isUuid($this->entryRequest), 422);
        $fields = $this->entryForm->writing(true);
        $world->getConnection()->transaction(function () use ($world, $fields): void {
            $kind = $this->entryForm->character ? 'person' : 'entry';
            $records = $this->entryForm->character ? $world->people() : $world->entries();
            $entry = $records->firstOrCreate(['creation_uuid' => $this->entryRequest], $this->entryForm->character ? [...$fields, 'roles' => []] : $fields);
            $this->linkReference($kind.':'.$entry->id);
        });
        $this->creatingReference = false;
    }

    public function selectChapter(int $id): array
    {
        $this->authorize('view', $this->manuscript);
        $this->manuscript->chapters()->whereKey($id)->firstOrFail();
        $this->chapterId = $id;

        return $this->chapterPayload();
    }

    public function saveChapter(int $id, array $writing): array
    {
        $this->authorize('update', $this->manuscript);
        $chapter = $this->manuscript->chapters()->whereKey($id)->firstOrFail();
        try {
            $fields = $this->form->validateWriting($writing);
            $chapter = $chapter->saveWriting($fields, Auth::user());
            $this->manuscript->refresh();

            return ['ok' => true, 'revision' => $chapter->revision, 'world_catalogue' => $this->referenceWorld()?->referenceCatalogue()->all() ?? [],
                'draft_number' => $this->manuscript->working_draft_number, 'manuscript_revision' => $this->manuscript->revision];
        } catch (ValidationException $exception) {
            return ['ok' => false, 'conflict' => collect($exception->errors())->keys()->contains('conflict'),
                'message' => collect($exception->errors())->flatten()->first()];
        }
    }

    public function addChapter(int $draftNumber, int $revision): array
    {
        $this->authorize('update', $this->manuscript);
        $chapter = $this->manuscript->addChapter(Auth::user(), $draftNumber, $revision);
        $this->chapterId = $chapter->id;

        return $this->chapterPayload();
    }

    public function moveChapter(int $id, string $direction, int $revision): array
    {
        $this->authorize('update', $this->manuscript);
        abort_unless(in_array($direction, ['up', 'down'], true), 422);
        $this->manuscript->moveChapter($id, $direction, $revision);
        $this->manuscript->refresh();

        return $this->chapterPayload();
    }

    public function archiveChapter(int $id, bool $archive, int $draftNumber, int $revision): array
    {
        $this->authorize('update', $this->manuscript);
        $this->manuscript->setChapterArchived($id, $archive, $draftNumber, $revision, Auth::user());
        $this->manuscript->refresh();
        if ($archive && $this->chapterId === $id) {
            $this->chapterId = $this->manuscript->chapters()->value('id');
        }
        if (! $archive) {
            $this->chapterId = $id;
            $this->archivedChapters = false;
        }

        return $this->chapterPayload();
    }

    public function changeTab(string $tab): void
    {
        $this->authorize('view', $this->manuscript);
        abort_unless(in_array($tab, ['write', 'drafts'], true), 422);
        $this->tab = $tab;
    }

    public function beginFinish(int $draftNumber): void
    {
        $this->authorize('finish', $this->manuscript);
        $this->manuscript->refresh();
        $this->manuscript->requireActiveDraft($draftNumber);
        $this->resetValidation();
        $this->finishForm->fill([
            'name' => 'Draft '.$this->manuscript->working_draft_number, 'note' => '',
            'revision' => $this->manuscript->revision, 'draft_number' => $this->manuscript->working_draft_number,
            'request_uuid' => (string) Str::uuid(),
        ]);
        $this->finishing = true;
    }

    public function finishDraft(ManuscriptDraftService $drafts): void
    {
        $this->authorize('finish', $this->manuscript);
        $this->finishForm->name = trim($this->finishForm->name);
        $draft = $drafts->finish($this->manuscript, Auth::user(), $this->finishForm->validate());
        $this->manuscript->refresh();
        $this->finishing = false;
        $this->tab = 'drafts';
        $this->selectedDraftId = $draft->id;
        $this->dispatch('studio-draft-finished', chapter: $this->chapterPayload());
    }

    public function undoFinish(int $id, int $revision, ManuscriptDraftService $drafts): array
    {
        $this->authorize('undoFinish', $this->manuscript);
        try {
            $drafts->undoFinish($this->manuscript, Auth::user(), $id, $revision);
            $this->selectedDraftId = null;
            $this->tab = 'write';

            return ['ok' => true, 'chapter' => $this->chapterPayload()];
        } catch (ValidationException $exception) {
            return ['ok' => false, 'message' => collect($exception->errors())->flatten()->first()];
        }
    }

    public function readDraft(int $id): void
    {
        $this->authorize('view', $this->manuscript);
        $this->manuscript->drafts()->whereKey($id)->firstOrFail();
        $this->selectedDraftId = $id;
        $this->readingDraft = true;
    }

    public function previewMarkdown(string $markdown): string
    {
        $this->authorize('view', $this->manuscript);
        abort_if(strlen($markdown) > 1048576, 422);

        return Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    public function chapterPayload(): array
    {
        $this->authorize('view', $this->manuscript);
        $this->manuscript->refresh();
        $chapter = $this->chapterId ? $this->manuscript->chapters()->whereKey($this->chapterId)->firstOrFail() : null;

        return ['can_write' => Auth::user()->can('update', $this->manuscript), 'id' => $chapter?->id, 'manuscript_id' => $this->manuscript->id, 'user_id' => Auth::id(),
            'chapter_number' => $chapter ? $this->manuscript->chapters()->pluck('id')->search($chapter->id) + 1 : 0,
            'title' => $chapter?->title ?? '', 'content_md' => $chapter?->content_md ?? '',
            'summary_md' => $chapter?->summary_md ?? '', 'revision' => $chapter?->revision ?? 1,
            'world_catalogue' => $this->referenceWorld()?->referenceCatalogue()->all() ?? [],
            'draft_number' => $this->manuscript->working_draft_number, 'manuscript_revision' => $this->manuscript->revision];
    }

    public function render()
    {
        $this->authorize('view', $this->manuscript);
        $this->manuscript->refresh();
        $selectedDraft = $this->selectedDraftId
            ? $this->manuscript->drafts()->with('chapters')->whereKey($this->selectedDraftId)->first() : null;

        return view('livewire.studio.manuscripts.workspace', [
            'availableWorlds' => $this->linkingWorld ? World::where('author_id', Auth::id())->orderBy('name')->get(['id', 'name']) : collect(),
            'canBrowseWorld' => $this->referenceWorld() !== null,
            'referenceTypes' => WorldEntryType::orderBy('name')->get(),
            'referenceMatches' => $this->referenceWorld()?->referenceCatalogue()->filter(fn ($entry) => $this->referenceSearch === '' || str_contains(mb_strtolower($entry['name'].' '.implode(' ', $entry['aliases'])), mb_strtolower($this->referenceSearch)))->take(50) ?? collect(),
            'chapterReferenceCount' => ChapterReference::withTrashed()->where('chapter_id', $this->chapterId)->count(),
            'chapterReferences' => ChapterReference::where('chapter_id', $this->chapterId)->when($this->archivedReferences, fn ($query) => $query->onlyTrashed())->get(),
            'referenceChapter' => $this->chapterId ? $this->manuscript->chapters()->find($this->chapterId) : null,
            'inspectedTarget' => $this->inspectedReference && $this->referenceWorld() ? $this->referenceWorld()->referenceTarget(explode(':', $this->inspectedReference)[0], (int) explode(':', $this->inspectedReference)[1], true) : null,
            'initialChapter' => $this->chapterPayload(),
            'archivedChapterRows' => $this->manuscript->chapters()->onlyTrashed()->get(),
            'chapters' => $this->manuscript->chapters()->get(['id', 'manuscript_id', 'title', 'position', 'summary_md', 'updated_at']),
            'drafts' => $this->manuscript->drafts()->withCount('chapters')->with('creator:id,name')->get(),
            'selectedDraft' => $selectedDraft,
            'currentChapters' => $selectedDraft ? $this->manuscript->chapters()->get()->keyBy('id') : collect(),
        ]);
    }
}
