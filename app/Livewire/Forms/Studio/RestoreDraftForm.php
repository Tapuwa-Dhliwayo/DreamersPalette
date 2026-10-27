<?php

namespace App\Livewire\Forms\Studio;

use Livewire\Form;

class RestoreDraftForm extends Form
{
    public int $revision = 0;

    public int $draft_number = 0;

    public function rules(): array
    {
        return ['revision' => ['required', 'integer', 'min:1'], 'draft_number' => ['required', 'integer', 'min:1']];
    }
}
