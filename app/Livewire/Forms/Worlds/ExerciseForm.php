<?php

namespace App\Livewire\Forms\Worlds;

use Livewire\Form;

class ExerciseForm extends Form
{
    public string $title = '';

    public string $purpose = '';

    public string $instructions = '';

    public string $summary = '';

    public array $context_keys = [];

    public array $passages = [];

    public int $revision = 1;

    public function fields(): array
    {
        return $this->validate(['title' => 'required|string|max:255', 'purpose' => 'nullable|string|max:10000', 'instructions' => 'nullable|string|max:10000', 'summary' => 'nullable|string|max:20000', 'context_keys' => 'array|max:100', 'context_keys.*' => 'string|max:80', 'passages' => 'array|max:20', 'passages.*' => 'integer|min:1']);
    }
}
