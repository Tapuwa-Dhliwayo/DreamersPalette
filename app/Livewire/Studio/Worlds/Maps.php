<?php

namespace App\Livewire\Studio\Worlds;

use App\Livewire\Forms\Worlds\MapForm;
use App\Models\World;
use App\Models\WorldMap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.studio')]
class Maps extends Component
{
    use WithFileUploads;

    public World $world;

    public MapForm $form;

    #[Url]
    public ?int $selected = null;

    public bool $archived = false;

    public bool $editing = false;

    public string $notice = '';

    #[Locked]
    public ?int $editingId = null;

    #[Url]
    public ?int $version = null;

    public function mount(World $world): void
    {
        $this->authorize('view', $world);
        $this->world = $world;
    }

    public function select(int $id): void
    {
        $this->authorize('view', $this->world);
        $this->selected = $this->world->maps()->withTrashed()->findOrFail($id)->id;
        $this->version = null;
    }

    public function edit(?int $id = null): void
    {
        $this->authorize('update', $this->world);
        $this->form->reset();
        $this->resetValidation();
        $this->editingId = $id;
        if ($id) {
            $map = $this->world->maps()->findOrFail($id);
            $this->form->fill([...$map->only(['title', 'unit', 'width_units', 'width_px', 'height_px', 'pins', 'journey', 'revision']), 'notes_md' => $map->notes_md ?? '']);
        }
        $this->editing = true;
    }

    public function addPin(float $x = 50, float $y = 50): void
    {
        $this->authorize('update', $this->world);
        if (count($this->form->pins) < 300) {
            $this->form->pins[] = ['key' => (string) Str::uuid(), 'name' => '', 'entry_id' => null, 'x' => round(max(0, min(100, $x)), 2), 'y' => round(max(0, min(100, $y)), 2)];
        }
    }

    public function updatedForm(mixed $value, string $key): void
    {
        if (preg_match('/^pins\.([0-9]+)\.entry_id$/', $key, $match) && $value) {
            $this->authorize('update', $this->world);
            $location = $this->world->entries()->findOrFail((int) $value);
            $this->form->pins[(int) $match[1]]['name'] = $location->name;
        }
    }

    public function removePin(int $index): void
    {
        $this->authorize('update', $this->world);
        if (! isset($this->form->pins[$index])) {
            return;
        }
        $key = $this->form->pins[$index]['key'];
        array_splice($this->form->pins, $index, 1);
        $this->form->journey = array_values(array_filter($this->form->journey, fn ($stop) => $stop !== $key));
    }

    public function addStop(): void
    {
        $this->authorize('update', $this->world);
        if (count($this->form->journey) < 500) {
            $this->form->journey[] = '';
        }
    }

    public function removeStop(int $index): void
    {
        $this->authorize('update', $this->world);
        if (array_key_exists($index, $this->form->journey)) {
            array_splice($this->form->journey, $index, 1);
        }
    }

    public function save(): void
    {
        $this->authorize('update', $this->world);
        $fields = $this->form->fields();
        $newPath = null;
        try {
            if ($this->form->image) {
                $newPath = $this->form->image->store('studio-maps', 'local');
                if (! $newPath) {
                    throw new \RuntimeException('Map image could not be saved.');
                }
                [$width, $height] = getimagesize(Storage::disk('local')->path($newPath));
                $fields = [...$fields, 'image_path' => $newPath, 'width_px' => $width, 'height_px' => $height];
            }
            DB::transaction(function () use ($fields): void {
                World::whereKey($this->world->id)->lockForUpdate()->firstOrFail();
                $map = $this->editingId ? $this->world->maps()->lockForUpdate()->findOrFail($this->editingId) : null;
                $retained = collect($map?->pins ?? [])->pluck('entry_id')->filter()->all();
                foreach ($fields['pins'] as $pin) {
                    if ($pin['entry_id']) {
                        $entry = $this->world->referenceTarget('entry', $pin['entry_id'], in_array($pin['entry_id'], $retained));
                        if ($entry->type->key !== 'location') {
                            throw ValidationException::withMessages(['form.pins' => 'Link a location entry from this world.']);
                        }
                    }
                }
                $keys = array_column($fields['pins'], 'key');
                foreach ($fields['journey'] as $key) {
                    if (! in_array($key, $keys, true)) {
                        throw ValidationException::withMessages(['form.journey' => 'A journey stop was removed. Choose an existing pin.']);
                    }
                }
                if ($map) {
                    $map->revise($fields, $this->form->revision, fn ($saved) => $saved->captureVersion(auth()->id()));
                } else {
                    $map = $this->world->maps()->create($fields);
                    $map->captureVersion(auth()->id());
                }
                $this->selected = $map->id;
            });
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $exception;
        }
        $this->editing = false;
        $this->version = null;
        $this->archived = false;
        $this->notice = 'Saved';
    }

    public function archiveRecord(int $id, int $revision, bool $archive): void
    {
        $this->authorize('update', $this->world);
        $this->world->maps()->withTrashed()->findOrFail($id)->setArchived($archive, auth()->id(), $revision);
        $this->selected = $archive ? null : $id;
        $this->version = null;
        if (! $archive) {
            $this->archived = false;
        }
        $this->notice = $archive ? 'Archived' : 'Restored';
    }

    public function render()
    {
        $this->authorize('view', $this->world);
        $map = $this->selected ? $this->world->maps()->withTrashed()->findOrFail($this->selected) : null;
        $viewMap = $map;
        if ($map && $this->version) {
            $snapshot = $map->versions()->findOrFail($this->version);
            $viewMap = (new WorldMap)->forceFill([...$snapshot->snapshot, 'id' => $map->id, 'revision' => $snapshot->revision]);
        }
        $retained = $this->editingId ? collect($this->world->maps()->find($this->editingId)?->pins ?? [])->pluck('entry_id')->filter()->all() : [];
        $locations = $this->world->entries()->withTrashed()->whereHas('type', fn ($query) => $query->where('key', 'location'))->where(fn ($query) => $query->whereNull('deleted_at')->orWhereIn('id', $retained))->get();

        return view('livewire.studio.worlds.maps', ['maps' => $this->world->maps()->when($this->archived, fn ($query) => $query->onlyTrashed())->get(), 'map' => $map, 'viewMap' => $viewMap, 'locations' => $locations]);
    }
}
