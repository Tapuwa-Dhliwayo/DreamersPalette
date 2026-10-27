<?php

namespace App\Livewire\Forms\Worlds;

use App\Models\World;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PlacementForm extends Form
{
    public string $name = '';

    public ?int $person_id = null;

    public ?int $system_id = null;

    public int $x = 0;

    public int $y = 0;

    public string $reason_md = '';

    public int $revision = 0;

    public function writing(World $world): array
    {
        return $this->validate(['name' => ['required', 'string', 'max:255'], 'person_id' => ['nullable', 'integer', 'prohibits:system_id', Rule::exists('studio_world_people', 'id')->where('world_id', $world->id)], 'system_id' => ['nullable', 'integer', Rule::exists('studio_magic_systems', 'id')->where('world_id', $world->id)], 'x' => ['required', 'integer', 'between:-100,100'], 'y' => ['required', 'integer', 'between:-100,100'], 'reason_md' => ['nullable', 'string', 'max:200000'], 'revision' => ['required', 'integer', 'min:0']]);
    }
}
