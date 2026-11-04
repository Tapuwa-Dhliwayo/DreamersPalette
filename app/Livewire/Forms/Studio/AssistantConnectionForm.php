<?php

namespace App\Livewire\Forms\Studio;

use App\Services\Worldbuilding\AssistantProviders;
use Illuminate\Validation\Rule;
use Livewire\Form;

class AssistantConnectionForm extends Form
{
    public string $name = '';

    public string $provider = 'codex';

    public string $model = 'default';

    public string $credential = '';

    public bool $enabled = true;

    public int $revision = 1;

    public function fields(): array
    {
        return $this->validate(['name' => 'required|string|max:255', 'provider' => ['required', Rule::in(array_keys(AssistantProviders::PROVIDERS))], 'model' => ['required', 'string', 'max:200', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9_\.\/:\-]*$/D'], 'credential' => 'nullable|string|max:4000', 'enabled' => 'boolean']);
    }
}
