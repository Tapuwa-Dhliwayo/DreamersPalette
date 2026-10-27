<?php

namespace App\Livewire\Forms\Worlds;

use App\Models\World;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ArcForm extends Form
{
    public string $title = '';

    public ?int $person_id = null;

    public ?int $manuscript_id = null;

    public string $measure = 'Pressure';

    public string $notes_md = '';

    public int $revision = 0;

    public function writing(World $world): array
    {
        return $this->validate(['title' => ['required', 'string', 'max:255'], 'person_id' => ['nullable', 'integer', Rule::exists('studio_world_people', 'id')->where('world_id', $world->id)], 'manuscript_id' => ['nullable', 'integer', Rule::exists('studio_manuscripts', 'id')->where('world_id', $world->id)->where('author_id', $world->author_id)], 'measure' => ['required', 'string', 'max:255'], 'notes_md' => ['nullable', 'string', 'max:200000'], 'revision' => ['required', 'integer', 'min:0']]);
    }
}
