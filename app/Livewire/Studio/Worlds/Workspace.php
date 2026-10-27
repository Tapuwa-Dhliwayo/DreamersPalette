<?php

namespace App\Livewire\Studio\Worlds;

use App\Livewire\Forms\Worlds\CaseForm;
use App\Livewire\Forms\Worlds\LinkForm;
use App\Livewire\Forms\Worlds\RuleForm;
use App\Livewire\Forms\Worlds\SystemForm;
use App\Livewire\Forms\Worlds\VariationForm;
use App\Models\MagicDecision;
use App\Models\MagicSystem;
use App\Models\MagicVariationMode;
use App\Models\Manuscript;
use App\Models\World;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.studio')]
class Workspace extends Component
{
    public World $world;

    public bool $archivedSystems = false;

    public bool $archivedParts = false;

    public SystemForm $systemForm;

    public RuleForm $ruleForm;

    public CaseForm $caseForm;

    public VariationForm $variationForm;

    public LinkForm $linkForm;

    #[Url(as: 'system')]
    public ?int $systemId = null;

    #[Url(as: 'manuscript')]
    public ?int $manuscriptId = null;

    #[Url(as: 'version')]
    public ?int $versionId = null;

    public string $search = '';

    public string $tab = 'overview';

    public bool $editingSystem = false;

    public bool $editingRule = false;

    public bool $editingCase = false;

    public bool $editingVariation = false;

    public bool $linking = false;

    public bool $readingVersion = false;

    public bool $reviewingSource = false;

    #[Locked]
    public bool $newSystem = false;

    #[Locked]
    public ?int $ruleId = null;

    #[Locked]
    public ?int $caseId = null;

    #[Locked]
    public ?int $sourceRuleId = null;

    #[Locked]
    public int $sourceVariationRevision = 0;

    #[Locked]
    public int $sourceRuleRevision = 0;

    public function mount(World $world): void
    {
        $this->authorize('view', $world);
        $this->world = $world;
        $this->systemId ??= $world->systems()->value('id');
        if ($this->systemId) {
            $this->system();
        }
        $this->manuscript();
        if ($this->versionId) {
            $this->viewVersion($this->versionId);
        }
    }

    private function system(): MagicSystem
    {
        $this->authorize('view', $this->world);

        return $this->world->systems()->when($this->versionId, fn ($query) => $query->withTrashed())->whereKey($this->systemId)->firstOrFail();
    }

    private function manuscript(): ?Manuscript
    {
        if (! $this->manuscriptId) {
            return null;
        }
        $manuscript = $this->world->manuscripts()->whereKey($this->manuscriptId)->firstOrFail();
        $this->authorize('view', $manuscript);

        return $manuscript;
    }

    public function selectSystem(int $id): void
    {
        $this->authorize('view', $this->world);
        $this->world->systems()->whereKey($id)->firstOrFail();
        $this->systemId = $id;
        $this->tab = 'overview';
        $this->versionId = null;
        $this->readingVersion = false;
    }

    public function changeTab(string $tab): void
    {
        $this->authorize('view', $this->world);
        abort_unless(in_array($tab, ['overview', 'rules', 'cases', 'history'], true), 422);
        $this->tab = $tab;
    }

    public function openRule(int $id): void
    {
        $rule = $this->system()->rules()->withTrashed()->whereKey($id)->firstOrFail();
        $this->archivedParts = $rule->trashed();
        $this->tab = 'rules';
        $this->dispatch('world-rule-opened', id: $id);
    }

    public function updatedManuscriptId(): void
    {
        $this->authorize('view', $this->world);
        $this->manuscript();
    }

    public function editSystem(bool $new = false): void
    {
        $this->authorize('update', $this->world);
        $this->resetValidation();
        $this->newSystem = $new;
        $this->systemForm->reset();
        if (! $new) {
            $system = $this->system();
            $this->systemForm->fill(['name' => $system->name, 'notes_md' => $system->notes_md,
                'aliases' => implode("\n", $system->aliases), 'revision' => $system->revision]);
        }
        $this->editingSystem = true;
    }

    public function saveSystem(): void
    {
        $this->authorize('update', $this->world);
        $this->systemForm->name = trim($this->systemForm->name);
        $fields = $this->systemForm->writing();
        if ($this->newSystem) {
            $this->systemId = MagicSystem::start($this->world, $fields, Auth::user())->id;
        } else {
            $this->system()->saveNotes($fields, $this->systemForm->revision, Auth::user());
        }
        $this->editingSystem = false;
        $this->archivedSystems = false;
        $this->dispatch('world-saved');
    }

    public function editRule(?int $id = null): void
    {
        $this->authorize('update', $this->world);
        $system = $this->system();
        $this->resetValidation();
        $this->ruleId = $id;
        $this->ruleForm->reset();
        $this->ruleForm->decision_id = MagicDecision::where('key', 'exploring')->value('id');
        if ($id) {
            $this->ruleForm->fill($system->rules()->whereKey($id)->firstOrFail()->only(array_keys($this->ruleForm->rules())));
        }
        $this->ruleForm->revision = $system->revision;
        $this->editingRule = true;
    }

    public function saveRule(): void
    {
        $this->authorize('update', $this->world);
        $this->ruleForm->title = trim($this->ruleForm->title);
        $fields = $this->ruleForm->validate();
        unset($fields['revision']);
        $this->system()->saveRule($this->ruleId, $fields, $this->ruleForm->revision, Auth::user());
        $this->editingRule = false;
        $this->tab = 'rules';
        $this->dispatch('world-saved');
    }

    public function editCase(?int $id = null): void
    {
        $this->authorize('update', $this->world);
        $system = $this->system();
        $this->resetValidation();
        $this->caseId = $id;
        $this->caseForm->reset();
        if ($id) {
            $case = $system->cases()->whereKey($id)->firstOrFail();
            $this->caseForm->fill($case->only(['title', 'situation_md', 'outcome_md', 'reasoning_md']));
            $this->caseForm->rule_ids = $case->rules()->pluck('studio_magic_rules.id')->all();
        }
        $this->caseForm->revision = $system->revision;
        $this->editingCase = true;
    }

    public function saveCase(): void
    {
        $this->authorize('update', $this->world);
        $system = $this->system();
        $this->caseForm->title = trim($this->caseForm->title);
        $fields = $this->caseForm->writing($system);
        unset($fields['revision']);
        $system->saveCase($this->caseId, $fields, $this->caseForm->revision, Auth::user());
        $this->editingCase = false;
        $this->tab = 'cases';
        $this->dispatch('world-saved');
    }

    public function adjustRule(int $id): void
    {
        $this->authorize('update', $this->world);
        $system = $this->system();
        $manuscript = $this->manuscript();
        abort_unless($manuscript, 422);
        $this->authorize('update', $manuscript);
        $system->rules()->whereKey($id)->firstOrFail();
        $this->resetValidation();
        $this->ruleId = $id;
        $this->variationForm->reset();
        $variation = $system->variations()->where('manuscript_id', $manuscript->id)->where('rule_id', $id)->first();
        if ($variation) {
            $this->variationForm->fill($variation->only(['mode_id', 'text_md', 'reason_md', 'revision']));
        }
        $this->variationForm->system_revision = $system->revision;
        $this->variationForm->manuscript_revision = $manuscript->revision;
        $this->variationForm->draft_number = $manuscript->working_draft_number;
        $this->editingVariation = true;
    }

    public function saveVariation(): void
    {
        $this->authorize('update', $this->world);
        $manuscript = $this->manuscript();
        abort_unless($manuscript, 422);
        $this->authorize('update', $manuscript);
        $this->system()->saveVariation($manuscript, $this->ruleId, $this->variationForm->writing());
        $this->editingVariation = false;
        $this->dispatch('world-saved');
    }

    public function compareSource(int $id): void
    {
        $system = $this->system();
        $manuscript = $this->manuscript();
        abort_unless($manuscript, 422);
        $rule = $system->rules()->whereKey($id)->firstOrFail();
        $variation = $system->variations()->where('manuscript_id', $manuscript->id)->where('rule_id', $id)->firstOrFail();
        $this->sourceRuleId = $id;
        $this->sourceRuleRevision = $rule->revision;
        $this->sourceVariationRevision = $variation->revision;
        $this->reviewingSource = true;
    }

    public function confirmSource(): void
    {
        $this->authorize('update', $this->world);
        $manuscript = $this->manuscript();
        abort_unless($manuscript, 422);
        $this->authorize('update', $manuscript);
        $this->system()->reviewSource($manuscript, $this->sourceRuleId, $this->sourceVariationRevision, $this->sourceRuleRevision);
        $this->reviewingSource = false;
    }

    public function beginLink(): void
    {
        $this->authorize('update', $this->world);
        $this->resetValidation();
        $this->linkForm->reset();
        $this->linking = true;
    }

    public function updatedLinkFormManuscriptId(): void
    {
        $this->authorize('update', $this->world);
        if (! $this->linkForm->manuscript_id) {
            return;
        }
        $manuscript = Manuscript::where('author_id', Auth::id())->whereKey($this->linkForm->manuscript_id)->firstOrFail();
        $this->authorize('update', $manuscript);
        $this->linkForm->revision = $manuscript->revision;
        $this->linkForm->draft_number = $manuscript->working_draft_number;
    }

    public function linkManuscript(): void
    {
        $this->authorize('update', $this->world);
        $fields = $this->linkForm->validate();
        $manuscript = Manuscript::where('author_id', Auth::id())->whereKey($fields['manuscript_id'])->firstOrFail();
        $this->authorize('update', $manuscript);
        $manuscript->linkWorld($this->world, $fields['revision'], $fields['draft_number']);
        $this->manuscriptId = $manuscript->id;
        $this->linking = false;
        $this->dispatch('world-saved');
    }

    public function updatedReadingVersion(bool $open): void
    {
        $this->authorize('view', $this->world);
        if (! $open) {
            $this->versionId = null;
        }
    }

    public function viewVersion(int $id): void
    {
        $this->system()->versions()->whereKey($id)->firstOrFail();
        $this->versionId = $id;
        $this->readingVersion = true;
    }

    public function archiveSystem(int $id, int $revision, bool $archive): void
    {
        $this->authorize('update', $this->world);
        $this->world->systems()->withTrashed()->whereKey($id)->firstOrFail()->setArchived($archive, Auth::id(), $revision);
        if ($this->systemId === $id) {
            $this->systemId = null;
            $this->versionId = null;
            $this->readingVersion = false;
        }
    }

    public function archivePart(string $kind, int $id, bool $archive, int $revision): void
    {
        $this->authorize('update', $this->world);
        if ($this->manuscriptId) {
            throw ValidationException::withMessages(['conflict' => 'Switch to World scope before archiving a world rule or case.']);
        }
        $this->system()->archivePart($kind, $id, $archive, $revision, Auth::user());
    }

    public function render()
    {
        $this->authorize('view', $this->world);
        $system = $this->systemId ? $this->system() : null;
        $manuscript = $this->manuscript();
        $sourceRule = $system && $this->sourceRuleId ? $system->rules()->whereKey($this->sourceRuleId)->first() : null;
        $sourceVariation = $sourceRule && $manuscript ? $system->variations()->where('manuscript_id', $manuscript->id)->where('rule_id', $sourceRule->id)->first() : null;

        return view('livewire.studio.worlds.workspace', [
            'system' => $system, 'manuscript' => $manuscript,
            'worlds' => World::where('author_id', Auth::id())->orderBy('name')->get(),
            'systems' => $this->world->systems()->when($this->archivedSystems, fn ($query) => $query->onlyTrashed())->when($this->search !== '', fn ($query) => $query->where(fn ($names) => $names->where('name', 'like', '%'.mb_substr($this->search, 0, 200).'%')->orWhere('aliases', 'like', '%'.mb_substr($this->search, 0, 200).'%')))->get(),
            'caseRuleChoices' => $system?->rules()->withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhereIn('id', $this->caseForm->rule_ids))->get() ?? collect(),
            'rules' => $system?->rules()->when($this->archivedParts, fn ($query) => $query->onlyTrashed())->with('decision')->get() ?? collect(),
            'cases' => $system?->cases()->when($this->archivedParts, fn ($query) => $query->onlyTrashed())->with('rules.decision')->get() ?? collect(),
            'variations' => $system && $manuscript ? $system->variations()->where('manuscript_id', $manuscript->id)->with('mode')->get()->keyBy('rule_id') : collect(),
            'versions' => $system?->versions()->get(['id', 'system_id', 'revision', 'created_at']) ?? collect(),
            'version' => $system && $this->versionId ? $system->versions()->whereKey($this->versionId)->first() : null,
            'sourceRule' => $sourceRule, 'sourceVariation' => $sourceVariation,
            'decisions' => MagicDecision::all(), 'modes' => MagicVariationMode::all(),
            'linkedManuscripts' => $this->world->manuscripts()->where('author_id', Auth::id())->get(),
            'availableManuscripts' => Manuscript::where('author_id', Auth::id())->whereNull('world_id')->orderBy('title')->get(),
        ]);
    }
}
