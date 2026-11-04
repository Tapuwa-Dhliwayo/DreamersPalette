<?php

namespace App\Livewire\Forms\Worlds;

use App\Services\Worldbuilding\SandboxProposals;
use Illuminate\Validation\Rule;
use Livewire\Form;

class SandboxIdeaForm extends Form
{
    public string $kind = 'thought';

    public string $title = '';

    public string $notes_md = '';

    public string $uncertainty = '';

    public string $alternative = '';

    public string $lane = '';

    public string $position = '0';

    public string $date_label = '';

    public string $aliases = '';

    public string $roles = '';

    public string $domain = '';

    public ?int $type_id = null;

    public string $measure = 'Pressure';

    public string $source_key = '';

    public string $target_key = '';

    public string $period = '';

    public bool $is_dependency = false;

    public int $revision = 1;

    public function fields(): array
    {
        $fields = $this->validate(['kind' => ['required', Rule::in(SandboxProposals::KINDS)], 'title' => 'required|string|max:255', 'notes_md' => 'nullable|string|max:50000', 'uncertainty' => 'nullable|string|max:255', 'alternative' => 'nullable|string|max:255', 'lane' => 'nullable|string|max:255', 'position' => 'required|numeric|between:-99999999999,99999999999', 'date_label' => 'nullable|string|max:255', 'aliases' => 'nullable|string|max:10000', 'roles' => 'nullable|string|max:3000', 'domain' => 'nullable|string|max:255', 'type_id' => 'nullable|integer|min:1', 'measure' => 'nullable|string|max:255', 'source_key' => 'nullable|string|max:80', 'target_key' => 'nullable|string|max:80', 'period' => 'nullable|string|max:255', 'is_dependency' => 'boolean']);
        $additional = collect($fields)->only(['domain', 'type_id', 'measure', 'source_key', 'target_key', 'period', 'is_dependency'])->all();
        $additional['aliases'] = array_values(array_filter(array_map('trim', preg_split('/\\r?\\n/', $this->aliases))));
        $additional['roles'] = array_values(array_filter(array_map('trim', explode(',', $this->roles))));
        $fields = collect($fields)->except(['aliases', 'roles', 'domain', 'type_id', 'measure', 'source_key', 'target_key', 'period', 'is_dependency'])->all();
        $fields['fields'] = $additional;

        return $fields;
    }
}
