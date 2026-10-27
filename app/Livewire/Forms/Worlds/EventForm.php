<?php

namespace App\Livewire\Forms\Worlds;

use App\Models\World;
use Illuminate\Validation\Rule;
use Livewire\Form;

class EventForm extends Form
{
    public string $title = '';

    public string $date_label = '';

    public string $position = '1';

    public string $notes_md = '';

    public array $person_ids = [];

    public int $revision = 0;

    public function writing(World $world): array
    {
        return $this->validate(['title' => ['required', 'string', 'max:255'], 'date_label' => ['nullable', 'string', 'max:255'], 'position' => ['required', 'numeric', 'between:-99999999999,99999999999'], 'notes_md' => ['nullable', 'string', 'max:200000'], 'person_ids' => ['array', 'max:200'], 'person_ids.*' => ['integer', 'distinct', Rule::exists('studio_world_people', 'id')->where('world_id', $world->id)], 'revision' => ['required', 'integer', 'min:0']]);
    }
}
