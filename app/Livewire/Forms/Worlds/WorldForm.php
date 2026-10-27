<?php

namespace App\Livewire\Forms\Worlds;

use Livewire\Form;

class WorldForm extends Form
{
    public string $name = '';

    public string $description = '';

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000']];
    }
}
