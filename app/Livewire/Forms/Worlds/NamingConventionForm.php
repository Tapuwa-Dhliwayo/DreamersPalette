<?php

namespace App\Livewire\Forms\Worlds;

use Livewire\Form;

class NamingConventionForm extends Form
{
    public string $name = '';

    public string $guidance = '';

    public string $examples = '';

    public string $source = '';

    public bool $reviewed = false;

    public bool $personal = false;

    public int $revision = 1;

    public function fields(): array
    {
        return $this->validate(['name' => 'required|string|max:255', 'guidance' => 'required|string|max:20000', 'examples' => 'nullable|string|max:10000', 'source' => 'nullable|string|max:2000', 'reviewed' => 'boolean']);
    }
}
