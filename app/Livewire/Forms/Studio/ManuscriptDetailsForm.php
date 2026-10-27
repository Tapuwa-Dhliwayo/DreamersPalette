<?php

namespace App\Livewire\Forms\Studio;

class ManuscriptDetailsForm extends ManuscriptForm
{
    public int $revision = 0;

    public function rules(): array
    {
        return [...parent::rules(), 'revision' => ['required', 'integer', 'min:1']];
    }
}
