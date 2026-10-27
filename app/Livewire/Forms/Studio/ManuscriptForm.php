<?php

namespace App\Livewire\Forms\Studio;

use Livewire\Form;

class ManuscriptForm extends Form
{
    public string $title = '';

    public string $description = '';

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000']];
    }
}
