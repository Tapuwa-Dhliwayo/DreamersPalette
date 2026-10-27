<?php

namespace App\Livewire\Forms\Worlds;

use Livewire\Form;

class InvestigationForm extends Form
{
    public string $title = '';

    public array $subjects = [];

    public string $question_md = '';

    public array $evidence = [];

    public string $open_questions_md = '';

    public string $conclusion_md = '';

    public bool $is_resolved = false;

    public int $revision = 1;

    public function fields(): array
    {
        return $this->validate([
            'title' => 'required|string|max:255', 'subjects' => 'array|list|max:40', 'subjects.*' => 'array:key,name', 'subjects.*.key' => ['required', 'distinct', 'regex:/^(person|entry|system):[1-9][0-9]*$/'],
            'question_md' => 'required|string|max:100000', 'evidence' => 'array|list|max:100', 'evidence.*' => 'array:text,source,supports', 'evidence.*.text' => 'required|string|max:10000', 'evidence.*.source' => 'required|string|max:1000', 'evidence.*.supports' => 'required|boolean',
            'open_questions_md' => 'nullable|string|max:100000', 'conclusion_md' => 'nullable|string|max:100000|required_if:is_resolved,true', 'is_resolved' => 'boolean',
        ]);
    }
}
