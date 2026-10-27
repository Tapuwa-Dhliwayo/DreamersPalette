<?php

namespace App\Livewire\Studio\Manuscripts;

use App\Livewire\Forms\Studio\ReleaseForm;
use App\Models\Book;
use App\Models\Manuscript;
use App\Models\ManuscriptRelease;
use App\Services\ManuscriptPublishing;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.studio')]
class Publishing extends Component
{
    public Manuscript $manuscript;

    public ReleaseForm $release;

    public string $notice = '';

    #[Locked]
    public string $requestUuid;

    public function mount(Manuscript $manuscript): void
    {
        $this->authorize('finish', $manuscript);
        $this->manuscript = $manuscript;
        $this->release->revision = $manuscript->revision;
        $this->release->public_synopsis = $manuscript->book_id ? (Book::find($manuscript->book_id)?->synopsis ?? '') : '';
        $this->requestUuid = (string) Str::uuid();
    }

    public function updatedRelease(): void
    {
        $this->requestUuid = (string) Str::uuid();
    }

    public function publish(ManuscriptPublishing $publishing): void
    {
        $this->authorize('finish', $this->manuscript);
        $fields = $this->release->validate();
        $publishing->publish($this->manuscript, $fields['draft_id'], $fields['revision'], auth()->user(), $this->requestUuid, $fields['public_synopsis'] ?? '');
        $this->manuscript->refresh();
        $this->release->revision = $this->manuscript->revision;
        $this->release->confirmed = false;
        $this->notice = 'Published from the selected finished draft.';
    }

    public function render()
    {
        $this->authorize('finish', $this->manuscript);

        return view('livewire.studio.manuscripts.publishing', ['drafts' => $this->manuscript->drafts()->get(),
            'source' => $this->release->draft_id ? $this->manuscript->drafts()->with('chapters')->findOrFail($this->release->draft_id) : null,
            'book' => $this->manuscript->book_id ? Book::withTrashed()->find($this->manuscript->book_id) : null,
            'releases' => ManuscriptRelease::where('manuscript_id', $this->manuscript->id)->latest('number')->get()]);
    }
}
