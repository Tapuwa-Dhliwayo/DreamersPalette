<?php

namespace App\Livewire\Forms\Worlds;

use App\Models\World;
use Livewire\Form;

class PersonForm extends Form
{
    public string $name = '';

    public string $aliases = '';

    public string $roles = '';

    public string $domain = '';

    public string $notes_md = '';

    public array $profile = [];

    public int $revision = 0;

    public function writing(World $world): array
    {
        return $this->validate(['name' => ['required', 'string', 'max:255'], 'aliases' => ['nullable', 'string', 'max:5000'], 'roles' => ['nullable', 'string', 'max:2000'], 'domain' => ['nullable', 'string', 'max:12000'], 'notes_md' => ['nullable', 'string', 'max:200000'], 'profile' => ['array:summary,appearance,background,personality,motivations,strengths,vulnerabilities'], 'profile.*' => ['nullable', 'string', 'max:30000'], 'revision' => ['required', 'integer', 'min:0']]);
    }
}
