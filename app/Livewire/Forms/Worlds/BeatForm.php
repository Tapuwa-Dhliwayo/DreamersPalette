<?php

namespace App\Livewire\Forms\Worlds;

use App\Models\World;
use Illuminate\Validation\Rule;
use Livewire\Form;

class BeatForm extends Form
{
    public string $title = '';

    public ?int $event_id = null;

    public string $position = '1';

    public int $intensity = 50;

    public string $notes_md = '';

    public int $revision = 0;

    public function writing(World $world): array
    {
        return $this->validate(['title' => ['required', 'string', 'max:255'], 'event_id' => ['nullable', 'integer', Rule::exists('studio_world_events', 'id')->where('world_id', $world->id)], 'position' => ['required', 'numeric', 'between:-99999999999,99999999999'], 'intensity' => ['required', 'integer', 'between:0,100'], 'notes_md' => ['nullable', 'string', 'max:200000'], 'revision' => ['required', 'integer', 'min:0']]);
    }
}
