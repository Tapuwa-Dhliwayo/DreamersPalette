<?php

namespace App\Livewire\Studio\Worlds;

use App\Livewire\Forms\Worlds\AlignmentForm;
use App\Livewire\Forms\Worlds\ArcForm;
use App\Livewire\Forms\Worlds\BeatForm;
use App\Livewire\Forms\Worlds\EntryForm;
use App\Livewire\Forms\Worlds\EventForm;
use App\Livewire\Forms\Worlds\PersonForm;
use App\Livewire\Forms\Worlds\PlacementForm;
use App\Livewire\Forms\Worlds\PositionForm;
use App\Models\AlignmentChart;
use App\Models\StoryArc;
use App\Models\World;
use App\Models\WorldEntryType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.studio')]
class Development extends Component
{
    public World $world;

    public EntryForm $entryForm;

    public bool $archived = false;

    public bool $archivedChildren = false;

    public int $typeFilter = 0;

    public PersonForm $personForm;

    public EventForm $eventForm;

    public ArcForm $arcForm;

    public BeatForm $beatForm;

    public AlignmentForm $alignmentForm;

    public PlacementForm $placementForm;

    public PositionForm $positionForm;

    #[Url]
    public string $section = 'people';

    #[Url]
    public ?int $selected = null;

    public string $search = '';

    public string $role = '';

    public bool $editing = false;

    public string $notice = '';

    #[Locked]
    public string $editingKind = 'person';

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $editingParent = null;

    private const SECTIONS = ['people', 'timeline', 'arcs', 'alignments', 'entries'];

    private const FORMS = ['entry' => 'entryForm', 'person' => 'personForm', 'event' => 'eventForm', 'arc' => 'arcForm', 'beat' => 'beatForm', 'alignment' => 'alignmentForm', 'placement' => 'placementForm'];

    public function mount(World $world): void
    {
        $this->authorize('view', $world);
        abort_unless(in_array($this->section, self::SECTIONS, true), 404);
        $this->world = $world;
    }

    public function changeSection(string $section): void
    {
        $this->authorize('view', $this->world);
        abort_unless(in_array($section, self::SECTIONS, true), 404);
        $this->section = $section;
        $this->selected = null;
        $this->search = '';
        $this->role = '';
        $this->archived = false;
        $this->typeFilter = 0;
        $this->notice = '';
    }

    public function select(int $id): void
    {
        $this->authorize('view', $this->world);
        $this->records()->when($this->archived, fn ($query) => $query->onlyTrashed())->whereKey($id)->firstOrFail();
        $this->selected = $id;
    }

    public function updatedArchived(): void
    {
        $this->selected = null;
    }

    public function archiveRecord(int $id, int $revision, bool $archive): void
    {
        $this->authorize('update', $this->world);
        $record = $this->records()->withTrashed()->whereKey($id)->firstOrFail();
        $record->setArchived($archive, Auth::id(), $revision);
        $this->selected = null;
        $this->notice = $archive ? 'Archived. Links and finished drafts are retained.' : 'Restored';
    }

    public function archiveChild(string $kind, int $id, int $revision, bool $archive): void
    {
        $this->authorize('update', $this->world);
        abort_unless(in_array($kind, ['beat', 'placement'], true), 404);
        $this->editingRecords($kind, $this->selected)->withTrashed()->whereKey($id)->firstOrFail()->setArchived($archive, Auth::id(), $revision);
        $this->notice = $archive ? 'Archived' : 'Restored';
    }

    private function records()
    {
        return match ($this->section) {
            'entries' => $this->world->entries()->with('type'),
            'people' => $this->world->people(),
            'timeline' => $this->world->events(),
            'arcs' => $this->world->arcs(),
            'alignments' => $this->world->alignmentCharts(),
            default => abort(404),
        };
    }

    private function editingRecords(string $kind, ?int $parent = null)
    {
        return match ($kind) {
            'entry' => $this->world->entries(),
            'person' => $this->world->people(),
            'event' => $this->world->events(),
            'arc' => $this->world->arcs(),
            'alignment' => $this->world->alignmentCharts(),
            'beat' => $this->world->arcs()->whereKey($parent)->firstOrFail()->beats(),
            'placement' => $this->world->alignmentCharts()->whereKey($parent)->firstOrFail()->placements(),
            default => abort(404),
        };
    }

    public function edit(string $kind, ?int $id = null): void
    {
        $this->authorize('update', $this->world);
        abort_unless(isset(self::FORMS[$kind]), 404);
        $this->resetValidation();
        $this->notice = '';
        $this->editingKind = $kind;
        $this->editingId = $id;
        $this->editingParent = in_array($kind, ['beat', 'placement'], true) ? $this->selected : null;
        $form = $this->{self::FORMS[$kind]};
        $form->reset();
        $query = $this->editingRecords($kind, $this->editingParent);
        if ($id) {
            $record = $query->whereKey($id)->firstOrFail();
            $fields = $record->only(array_keys($form->all()));
            if (in_array($kind, ['person', 'entry'], true)) {
                $fields['aliases'] = implode("\n", $record->aliases);
                if ($kind === 'person') {
                    $fields['roles'] = implode(', ', $record->roles);
                }
            }
            if ($kind === 'event') {
                $fields['person_ids'] = $record->people()->pluck('studio_world_people.id')->all();
            }
            // Markdown and optional text use empty strings in typed form properties.
            foreach ($fields as $field => $value) {
                if ($value === null && is_string($form->{$field})) {
                    $fields[$field] = '';
                }
            }
            $form->fill($fields);
        }
        if (! $id && in_array($kind, ['event', 'beat'], true)) {
            $form->position = (string) (($query->max('position') ?? 0) + 1);
        }
        $this->editing = true;
    }

    public function placeAt(int $x, int $y): void
    {
        $this->authorize('update', $this->world);
        abort_unless($this->section === 'alignments', 404);
        $this->edit('placement');
        $this->placementForm->x = max(-100, min(100, $x));
        $this->placementForm->y = max(-100, min(100, $y));
    }

    public function updatedPlacementFormPersonId(): void
    {
        $this->authorize('update', $this->world);
        if ($this->placementForm->person_id) {
            $person = $this->world->people()->whereKey($this->placementForm->person_id)->firstOrFail();
            $this->placementForm->name = $person->name;
            $this->placementForm->system_id = null;
        }
    }

    public function updatedPlacementFormSystemId(): void
    {
        $this->authorize('update', $this->world);
        if ($this->placementForm->system_id) {
            $system = $this->world->systems()->whereKey($this->placementForm->system_id)->firstOrFail();
            $this->placementForm->name = $system->name;
            $this->placementForm->person_id = null;
        }
    }

    public function movePlacement(int $id, int $x, int $y, int $revision): bool
    {
        $this->authorize('update', $this->world);
        abort_unless($this->section === 'alignments', 404);
        $placement = $this->world->alignmentCharts()->whereKey($this->selected)->firstOrFail()->placements()->whereKey($id)->firstOrFail();
        $this->resetValidation();
        $this->positionForm->fill(compact('x', 'y', 'revision'));
        $coordinates = $this->positionForm->coordinates();
        unset($coordinates['revision']);
        $placement->revise($coordinates, $revision);
        $this->notice = 'Position saved';

        return true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->world);
        $kind = $this->editingKind;
        abort_unless(isset(self::FORMS[$kind]), 404);
        $form = $this->{self::FORMS[$kind]};
        $fields = $kind === 'entry' ? $form->writing() : $form->writing($this->world);
        $revision = $form->revision;
        unset($fields['revision']);
        if ($kind === 'person') {
            $fields['aliases'] = $this->terms($fields['aliases'], '/\R/');
            $fields['roles'] = $this->terms($fields['roles'], '/[,\r\n]+/');
        }
        $personIds = $fields['person_ids'] ?? [];
        unset($fields['person_ids']);
        $query = $this->editingRecords($kind, $this->editingParent);
        $record = $this->world->getConnection()->transaction(function () use ($query, $fields, $revision, $kind, $personIds) {
            $record = $this->editingId ? $query->whereKey($this->editingId)->firstOrFail() : $query->make();
            if ($kind === 'event' && $record->exists && $record->calendar_id && ($fields['date_label'] ?? '') !== ($record->date_label ?? '')) {
                throw ValidationException::withMessages(['eventForm.date_label' => 'This event uses a calendar date. Change or remove it in Calendars first.']);
            }
            $syncPeople = $kind === 'event' ? fn ($event) => $event->people()->sync($personIds) : null;
            if ($record->exists) {
                $record->revise($fields, $revision, $syncPeople);

                return $record;
            }
            $record->fill($fields)->save();
            $syncPeople?->__invoke($record);

            return $record;
        });
        if (! in_array($kind, ['beat', 'placement'], true)) {
            $this->section = match ($kind) {
                'entry' => 'entries', 'person' => 'people', 'event' => 'timeline', 'arc' => 'arcs', 'alignment' => 'alignments'
            };
            $this->selected = $record->id;
        }
        $this->editing = false;
        $this->archived = false;
        $this->archivedChildren = false;
        $this->notice = 'Saved';
        $this->dispatch('world-saved');
    }

    private function terms(string $text, string $separator): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split($separator, $text)))));
    }

    public function render()
    {
        $this->authorize('view', $this->world);
        abort_unless(in_array($this->section, self::SECTIONS, true), 404);
        $query = $this->records()->when($this->archived, fn ($query) => $query->onlyTrashed());
        if ($this->section === 'entries' && $this->typeFilter) {
            $query->where('type_id', $this->typeFilter);
        }
        if ($this->search !== '') {
            $column = in_array($this->section, ['people', 'entries'], true) ? 'name' : 'title';
            $query->where(function ($query) use ($column): void {
                $query->where($column, 'like', '%'.$this->search.'%');
                if (in_array($this->section, ['people', 'entries'], true)) {
                    $query->orWhere('aliases', 'like', '%'.$this->search.'%');
                }
            });
        }
        if ($this->section === 'people' && $this->role !== '') {
            $query->whereJsonContains('roles', $this->role);
        }
        $entries = $query->get();
        $entry = $this->selected ? $this->records()->withTrashed()->whereKey($this->selected)->first() : $entries->first();
        if ($entry && ! $this->selected) {
            $this->selected = $entry->id;
        }
        $beats = $entry instanceof StoryArc ? $entry->beats()->when($this->archivedChildren, fn ($query) => $query->onlyTrashed())->with('event')->get() : collect();

        return view('livewire.studio.worlds.development', [
            'editingCalendarEvent' => $this->editingKind === 'event' && $this->editingId ? $this->world->events()->find($this->editingId) : null,
            'types' => WorldEntryType::orderBy('name')->get(),
            'entries' => $entries, 'entry' => $entry,
            'people' => $this->world->people()->withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhereIn('id', match ($this->editingKind) {
                'event' => $this->eventForm->person_ids, 'arc' => [$this->arcForm->person_id], 'placement' => [$this->placementForm->person_id], default => []
            }))->get(),
            'roles' => $this->world->people()->when($this->archived, fn ($query) => $query->onlyTrashed())->get()->flatMap->roles->unique()->sort()->values(),
            'events' => $this->world->events()->get(),
            'manuscripts' => $this->world->manuscripts()->withTrashed()->where('author_id', Auth::id())->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $this->arcForm->manuscript_id))->get(),
            'systems' => $this->world->systems()->withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $this->placementForm->system_id))->get(),
            'availableEvents' => $this->world->events()->withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $this->beatForm->event_id))->get(),
            'beats' => $beats,
            'arcSeries' => $beats->map(fn ($beat, $index) => ['step' => $index + 1, 'title' => $beat->title, 'intensity' => $beat->intensity])->all(),
            'placements' => $entry instanceof AlignmentChart ? $entry->placements()->when($this->archivedChildren, fn ($query) => $query->onlyTrashed())->with(['person', 'system'])->get() : collect(),
        ]);
    }
}
