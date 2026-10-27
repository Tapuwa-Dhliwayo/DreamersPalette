<?php

namespace App\Livewire\Studio\Worlds;

use App\Livewire\Forms\Worlds\ComparisonForm;
use App\Livewire\Forms\Worlds\RelationshipForm;
use App\Models\World;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.studio')]
class Thinking extends Component
{
    public World $world;

    public RelationshipForm $relationshipForm;

    public ComparisonForm $comparisonForm;

    #[Url]
    public string $section = 'relationships';

    #[Url]
    public ?int $selected = null;

    public bool $archived = false;

    public string $search = '';

    public string $subjectFilter = '';

    public string $notice = '';

    public bool $editing = false;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public string $editingSection = 'relationships';

    public function mount(World $world): void
    {
        $this->authorize('view', $world);
        abort_unless(in_array($this->section, ['relationships', 'comparisons'], true), 404);
        $this->world = $world;
    }

    private function records(?string $section = null)
    {
        return match ($section ?? $this->section) {
            'relationships' => $this->world->relationships(),
            'comparisons' => $this->world->comparisons(),
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
        $this->relationshipForm->reset();
        $this->comparisonForm->reset();
        $this->editingId = $id;
        $this->editingSection = $this->section;
        if ($id) {
            $record = $this->records()->findOrFail($id);
            $form = $this->section === 'relationships' ? $this->relationshipForm : $this->comparisonForm;
            $form->fill(array_intersect_key($record->toArray(), $form->all()));
        }
        $this->editing = true;
    }

    public function addCriterion(): void
    {
        $this->authorize('update', $this->world);
        if (count($this->comparisonForm->criteria) < 20) {
            $this->comparisonForm->criteria[] = '';
            foreach ($this->comparisonForm->subjects as &$subject) {
                $subject['cells'][] = '';
            }
        }
    }

    public function removeCriterion(int $index): void
    {
        $this->authorize('update', $this->world);
        if (! array_key_exists($index, $this->comparisonForm->criteria)) {
            return;
        }
        array_splice($this->comparisonForm->criteria, $index, 1);
        foreach ($this->comparisonForm->subjects as &$subject) {
            array_splice($subject['cells'], $index, 1);
        }
    }

    public function addSubject(): void
    {
        $this->authorize('update', $this->world);
        if (count($this->comparisonForm->subjects) < 40) {
            $this->comparisonForm->subjects[] = ['key' => '', 'cells' => array_fill(0, count($this->comparisonForm->criteria), '')];
        }
    }

    public function removeSubject(int $index): void
    {
        $this->authorize('update', $this->world);
        if (array_key_exists($index, $this->comparisonForm->subjects)) {
            array_splice($this->comparisonForm->subjects, $index, 1);
        }
    }

    private function subjectName(string $key, array $retained): string
    {
        [$kind, $id] = explode(':', $key);

        // Only an already-linked subject may remain archived when editing this record.
        return $this->world->referenceTarget($kind, (int) $id, in_array($key, $retained, true))->name;
    }

    public function save(): void
    {
        $this->authorize('update', $this->world);
        abort_unless($this->editing, 409);
        $section = $this->editingSection;
        $form = $section === 'relationships' ? $this->relationshipForm : $this->comparisonForm;
        $fields = $form->fields();
        $saved = DB::transaction(function () use ($section, $fields, $form): Model {
            // Serialise with world archive; an open editor cannot write into an archived world.
            World::whereKey($this->world->id)->lockForUpdate()->firstOrFail();
            $record = $this->editingId ? $this->records($section)->lockForUpdate()->findOrFail($this->editingId) : null;
            $retained = $record ? ($section === 'relationships' ? [$record->source_key, $record->target_key] : array_column($record->subjects, 'key')) : [];
            if ($section === 'relationships') {
                $fields['source_name'] = $this->subjectName($fields['source_key'], $retained);
                $fields['target_name'] = $this->subjectName($fields['target_key'], $retained);
            } else {
                $fields['subjects'] = array_map(function (array $subject) use ($retained, $fields): array {
                    return ['key' => $subject['key'], 'name' => $this->subjectName($subject['key'], $retained), 'cells' => array_map(fn ($index) => $subject['cells'][$index] ?? '', array_keys($fields['criteria']))];
                }, $fields['subjects']);
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
        if (! $archive) {
            $this->archived = false;
        }
        $this->notice = $archive ? 'Archived. You can restore it from Archived.' : 'Restored';
    }

    public function render()
    {
        $this->authorize('view', $this->world);
        $records = $this->records()->when($this->archived, fn ($query) => $query->onlyTrashed());
        if ($this->search !== '') {
            $records->where(function ($query): void {
                $column = $this->section === 'relationships' ? 'label' : 'title';
                $query->where($column, 'like', '%'.$this->search.'%');
                if ($this->section === 'relationships') {
                    $query->orWhere('source_name', 'like', '%'.$this->search.'%')->orWhere('target_name', 'like', '%'.$this->search.'%');
                }
            });
        }
        if ($this->section === 'relationships' && $this->subjectFilter !== '') {
            $records->where(fn ($query) => $query->where('source_key', $this->subjectFilter)->orWhere('target_key', $this->subjectFilter));
        }
        $record = $this->selected ? $this->records()->withTrashed()->findOrFail($this->selected) : null;
        $catalogue = $this->world->referenceCatalogue()->keyBy('key');
        $choices = $catalogue->all();
        $original = $this->editingId ? $this->records($this->editingSection)->find($this->editingId) : null;
        if ($original) {
            $retained = $this->editingSection === 'relationships' ? [$original->source_key => $original->source_name, $original->target_key => $original->target_name] : collect($original->subjects)->pluck('name', 'key')->all();
            foreach ($retained as $key => $name) {
                if (! isset($choices[$key])) {
                    $choices[$key] = ['key' => $key, 'name' => $name, 'type' => 'Archived'];
                }
            }
        }

        return view('livewire.studio.worlds.thinking', ['records' => $records->get(), 'record' => $record, 'catalogue' => $catalogue, 'choices' => $choices, 'dependencyImpact' => $this->section === 'relationships' && $record?->is_dependency ? $this->world->dependencyImpact($record->target_key) : null]);
    }
}
