<?php

namespace App\Livewire\Studio\Worlds;

use App\Livewire\Forms\Worlds\InvestigationForm;
use App\Livewire\Forms\Worlds\KnowledgeForm;
use App\Models\World;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.studio')]
class Reasoning extends Component
{
    public World $world;

    public InvestigationForm $investigationForm;

    public KnowledgeForm $knowledgeForm;

    #[Url]
    public string $section = 'investigations';

    #[Url]
    public ?int $selected = null;

    public string $search = '';

    public string $knowerFilter = '';

    public bool $archived = false;

    public bool $editing = false;

    public string $notice = '';

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public string $editingSection = 'investigations';

    public function mount(World $world): void
    {
        $this->authorize('view', $world);
        abort_unless(in_array($this->section, ['investigations', 'knowledge'], true), 404);
        $this->world = $world;
    }

    private function records(?string $section = null)
    {
        return match ($section ?? $this->section) {
            'investigations' => $this->world->investigations(),
            'knowledge' => $this->world->knowledge(),
            default => abort(404),
        };
    }

    public function select(int $id): void
    {
        $this->authorize('view', $this->world);
        $this->selected = $this->records()->withTrashed()->findOrFail($id)->id;
        $this->notice = '';
    }

    public function edit(?int $id = null): void
    {
        $this->authorize('update', $this->world);
        $this->resetValidation();
        $this->investigationForm->reset();
        $this->knowledgeForm->reset();
        $this->editingId = $id;
        $this->editingSection = $this->section;
        if ($id) {
            $record = $this->records()->findOrFail($id);
            $form = $this->section === 'investigations' ? $this->investigationForm : $this->knowledgeForm;
            $form->fill(array_intersect_key($record->toArray(), $form->all()));
        }
        $this->editing = true;
    }

    public function addRow(string $field): void
    {
        $this->authorize('update', $this->world);
        abort_unless($this->editing, 409);
        match ($field) {
            'subjects' => count($this->investigationForm->subjects) < 40 ? $this->investigationForm->subjects[] = ['key' => ''] : null,
            'evidence' => count($this->investigationForm->evidence) < 100 ? $this->investigationForm->evidence[] = ['text' => '', 'source' => '', 'supports' => true] : null,
            'knowers' => count($this->knowledgeForm->knowers) < 100 ? $this->knowledgeForm->knowers[] = ['key' => '', 'belief' => '', 'event_id' => null] : null,
            default => abort(404),
        };
    }

    public function removeRow(string $field, int $index): void
    {
        $this->authorize('update', $this->world);
        $form = match ($field) {
            'subjects', 'evidence' => $this->investigationForm, 'knowers' => $this->knowledgeForm, default => abort(404)
        };
        if (array_key_exists($index, $form->$field)) {
            $rows = $form->$field;
            array_splice($rows, $index, 1);
            $form->$field = $rows;
        }
    }

    public function save(): void
    {
        $this->authorize('update', $this->world);
        abort_unless($this->editing, 409);
        $section = $this->editingSection;
        $form = $section === 'investigations' ? $this->investigationForm : $this->knowledgeForm;
        $fields = [...$form->fields(), 'updated_by' => auth()->id()];
        $saved = DB::transaction(function () use ($section, $form, $fields) {
            World::whereKey($this->world->id)->lockForUpdate()->firstOrFail();
            $record = $this->editingId ? $this->records($section)->lockForUpdate()->findOrFail($this->editingId) : null;
            $field = $section === 'investigations' ? 'subjects' : 'knowers';
            $retained = array_column($record?->$field ?? [], 'key');
            foreach ($fields[$field] as &$subject) {
                [$kind, $id] = explode(':', $subject['key']);
                $subject['name'] = $this->world->referenceTarget($kind, (int) $id, in_array($subject['key'], $retained, true))->name;
                if ($section === 'knowledge') {
                    $eventId = $subject['event_id'] ?? null;
                    $old = collect($record?->knowers ?? [])->firstWhere('key', $subject['key']);
                    $event = $eventId ? $this->world->events()->withTrashed()->findOrFail($eventId) : null;
                    if ($event?->trashed() && (int) ($old['event_id'] ?? 0) !== (int) $eventId) {
                        throw ValidationException::withMessages(['knowledgeForm.knowers' => 'Restore an archived event before making a new link.']);
                    }
                    $subject['event_id'] = $event?->id;
                    $subject['event_title'] = $event?->title;
                }
            }
            unset($subject);
            if ($section === 'knowledge') {
                $manuscriptId = $fields['manuscript_id'];
                if (! $manuscriptId && $fields['reveal_chapter_id']) {
                    throw ValidationException::withMessages(['knowledgeForm.manuscript_id' => 'Choose the manuscript for this reveal.']);
                }
                if ($manuscriptId) {
                    $manuscript = $this->world->manuscripts()->findOrFail($manuscriptId);
                    $this->authorize('view', $manuscript);
                    $chapter = $manuscript->chapters()->withTrashed()->findOrFail($fields['reveal_chapter_id']);
                    if ($chapter->trashed() && $record?->reveal_chapter_id !== $chapter->id) {
                        throw ValidationException::withMessages(['knowledgeForm.reveal_chapter_id' => 'Restore this chapter before making a new reveal link.']);
                    }
                }
            }
            if ($record) {
                $record->revise($fields, $form->revision);

                return $record;
            }

            return $this->records($section)->create($fields);
        });
        $this->section = $section;
        $this->selected = $saved->id;
        $this->archived = false;
        $this->editing = false;
        $this->notice = 'Saved';
        $this->dispatch('world-saved');
    }

    public function archiveRecord(int $id, int $revision, bool $archive): void
    {
        $this->authorize('update', $this->world);
        $this->records()->withTrashed()->findOrFail($id)->setArchived($archive, auth()->id(), $revision);
        $this->selected = $archive ? null : $id;
        $this->archived = $archive;
        $this->notice = $archive ? 'Archived' : 'Restored';
    }

    public function render()
    {
        $this->authorize('view', $this->world);
        $query = $this->records()->when($this->archived, fn ($query) => $query->onlyTrashed())->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'));
        $records = $query->get();
        if ($this->section === 'knowledge' && $this->knowerFilter) {
            $records = $records->filter(fn ($record) => in_array($this->knowerFilter, array_column($record->knowers, 'key'), true));
        }
        $record = $this->selected ? $this->records()->withTrashed()->findOrFail($this->selected) : null;
        $choices = $this->world->referenceCatalogue()->keyBy('key')->all();
        $original = $this->editingId ? $this->records($this->editingSection)->find($this->editingId) : null;
        foreach (($this->editingSection === 'investigations' ? $original?->subjects : $original?->knowers) ?? [] as $subject) {
            $choices[$subject['key']] ??= ['key' => $subject['key'], 'name' => $subject['name'], 'type' => 'Archived'];
        }
        $manuscripts = $this->world->manuscripts()->with('chapters')->get();
        $revealOrder = $this->world->knowledge()->whereNotNull('reveal_chapter_id')->with(['manuscript', 'revealChapter'])->get()->sortBy(fn ($fact) => sprintf('%020d:%020d', $fact->manuscript_id, $fact->revealChapter?->position ?? PHP_INT_MAX));

        return view('livewire.studio.worlds.reasoning', ['records' => $records, 'record' => $record, 'choices' => $choices, 'events' => $this->world->events()->withTrashed()->get(), 'manuscripts' => $manuscripts, 'revealOrder' => $revealOrder]);
    }
}
