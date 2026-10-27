<?php

namespace App\Livewire\Studio\Manuscripts;

use App\Livewire\Forms\Studio\RestoreDraftForm;
use App\Models\Manuscript;
use App\Services\ManuscriptDraftService;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.studio')]
class RestoreDraft extends Component
{
    public Manuscript $manuscript;

    public RestoreDraftForm $form;

    #[Locked]
    public int $draftId;

    #[Locked]
    public string $requestUuid;

    public function mount(Manuscript $manuscript, int $draft): void
    {
        $this->authorize('restoreDraft', $manuscript);
        $this->manuscript = $manuscript;
        $this->draftId = $manuscript->drafts()->findOrFail($draft)->id;
        $this->requestUuid = (string) Str::uuid();
        $this->form->fill(['revision' => $manuscript->revision, 'draft_number' => $manuscript->working_draft_number]);
    }

    public function restore(ManuscriptDraftService $drafts): void
    {
        $this->authorize('restoreDraft', $this->manuscript);
        $drafts->restore($this->manuscript, auth()->user(), $this->draftId, $this->form->validate(), $this->requestUuid);
        $this->redirectRoute('studio.manuscript', $this->manuscript);
    }

    public function render()
    {
        $this->authorize('restoreDraft', $this->manuscript);

        return view('livewire.studio.manuscripts.restore-draft', ['draft' => $this->manuscript->drafts()->with('chapters')->findOrFail($this->draftId)]);
    }
}
