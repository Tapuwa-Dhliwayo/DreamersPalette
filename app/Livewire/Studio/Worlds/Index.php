<?php

namespace App\Livewire\Studio\Worlds;

use App\Livewire\Forms\Worlds\WorldForm;
use App\Models\World;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.studio')]
class Index extends Component
{
    public WorldForm $form;

    public bool $creating = false;

    public bool $archived = false;

    public function createWorld(): void
    {
        $this->authorize('create', World::class);
        $this->form->name = trim($this->form->name);
        $world = new World($this->form->validate());
        $world->author_id = Auth::id();
        $world->save();
        $this->redirectRoute('studio.world', $world);
    }

    public function archiveRecord(int $id, bool $archive): void
    {
        $record = World::withTrashed()->where('author_id', Auth::id())->findOrFail($id);
        $this->authorize($archive ? 'archive' : 'restore', $record);
        $record->setArchived($archive, Auth::id());
    }

    public function render()
    {
        $this->authorize('viewAny', World::class);

        return view('livewire.studio.worlds.index', [
            'worlds' => World::where('author_id', Auth::id())->when($this->archived, fn ($query) => $query->onlyTrashed())->withCount(['systems', 'manuscripts'])->latest('updated_at')->get(),
        ]);
    }
}
