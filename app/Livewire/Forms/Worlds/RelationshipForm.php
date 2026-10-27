<?php

namespace App\Livewire\Forms\Worlds;

use Illuminate\Validation\Rule;
use Livewire\Form;

class RelationshipForm extends Form
{
    public string $source_key = '';

    public string $target_key = '';

    public string $label = '';

    public string $period = '';

    public string $notes_md = '';

    public bool $is_dependency = false;

    public int $revision = 1;

    public function fields(): array
    {
        return $this->validate([
            'source_key' => ['required', 'max:40', 'regex:/^(person|entry|system):[1-9][0-9]*$/'],
            'target_key' => ['required', 'max:40', Rule::notIn([$this->source_key]), 'regex:/^(person|entry|system):[1-9][0-9]*$/'],
            'label' => ['required', 'string', 'max:255'],
            'is_dependency' => ['boolean'],
            'period' => ['nullable', 'string', 'max:255'],
            'notes_md' => ['nullable', 'string', 'max:100000'],
        ]);
    }
}
