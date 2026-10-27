<?php

namespace App\Livewire\Forms\Worlds;

use Livewire\Form;

class ComparisonForm extends Form
{
    public string $title = '';

    public string $notes_md = '';

    public array $criteria = [''];

    public array $subjects = [];

    public int $revision = 1;

    public function fields(): array
    {
        return $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'notes_md' => ['nullable', 'string', 'max:100000'],
            'criteria' => ['required', 'array', 'list', 'min:1', 'max:20'],
            'criteria.*' => ['required', 'string', 'max:120', 'distinct:ignore_case'],
            'subjects' => ['required', 'array', 'list', 'min:1', 'max:40'],
            'subjects.*.key' => ['required', 'max:40', 'regex:/^(person|entry|system):[1-9][0-9]*$/', 'distinct'],
            'subjects.*.cells' => ['present', 'array', 'list', 'max:20'],
            'subjects.*.cells.*' => ['nullable', 'string', 'max:10000'],
        ]);
    }
}
