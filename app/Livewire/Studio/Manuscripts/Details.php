<?php

namespace App\Livewire\Studio\Manuscripts;

use App\Livewire\Forms\Studio\ManuscriptDetailsForm;
use App\Models\Manuscript;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.studio')]
class Details extends Component
{
    public Manuscript $manuscript;

    public ManuscriptDetailsForm $form;

    public string $notice = '';

    public function mount(Manuscript $manuscript): void
    {
        $this->authorize('update', $manuscript);
        $this->manuscript = $manuscript;
        $this->form->fill(['title' => $manuscript->title, 'description' => $manuscript->description ?? '', 'revision' => $manuscript->revision]);
    }

    public function save(): void
    {
        $this->authorize('update', $this->manuscript);
        $this->form->title = trim($this->form->title);
        $fields = $this->form->validate();
        unset($fields['revision']);
        $this->manuscript->updateDetails($fields, $this->form->revision);
        $this->manuscript->refresh();
        $this->form->revision = $this->manuscript->revision;
        $this->notice = 'Saved';
    }

    public function render()
    {
        $this->authorize('update', $this->manuscript);

        return view('livewire.studio.manuscripts.details');
    }
}
