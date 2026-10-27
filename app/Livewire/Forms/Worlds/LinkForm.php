<?php

namespace App\Livewire\Forms\Worlds;

use Livewire\Form;

class LinkForm extends Form
{
    public int $manuscript_id = 0;

    public int $revision = 1;

    public int $draft_number = 1;

    public function rules(): array
    {
        return ['manuscript_id' => ['required', 'integer', 'min:1'], 'revision' => ['required', 'integer', 'min:1'], 'draft_number' => ['required', 'integer', 'min:1']];
    }
}
