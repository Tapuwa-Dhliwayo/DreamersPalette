<?php

namespace App\Livewire\Forms\Worlds;

use Livewire\Form;

class KnowledgeForm extends Form
{
    public string $title = '';

    public string $truth_md = '';

    public array $knowers = [];

    public ?int $manuscript_id = null;

    public ?int $reveal_chapter_id = null;

    public string $notes_md = '';

    public int $revision = 1;

    public function fields(): array
    {
        return $this->validate([
            'title' => 'required|string|max:255', 'truth_md' => 'nullable|string|max:100000', 'knowers' => 'array|list|max:100', 'knowers.*' => 'array:key,name,belief,event_id,event_title', 'knowers.*.key' => ['required', 'distinct', 'regex:/^(person|entry|system):[1-9][0-9]*$/'],
            'knowers.*.belief' => 'required|string|max:10000', 'knowers.*.event_id' => 'nullable|integer|min:1',
            'manuscript_id' => 'nullable|integer|min:1', 'reveal_chapter_id' => 'nullable|integer|min:1|required_with:manuscript_id', 'notes_md' => 'nullable|string|max:100000',
        ]);
    }
}
