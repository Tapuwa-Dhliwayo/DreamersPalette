<?php

namespace App\Livewire\Forms\Worlds;

use Illuminate\Validation\Rule;
use Livewire\Form;

class SandboxLinkForm extends Form
{
    public ?int $source_id = null;

    public ?int $target_id = null;

    public string $kind = 'connection';

    public string $label = '';

    public string $notes_md = '';

    public function fields(): array
    {
        return $this->validate(['source_id' => 'required|integer|min:1|different:target_id', 'target_id' => 'required|integer|min:1', 'kind' => ['required', Rule::in(['connection', 'before', 'participant', 'relationship', 'depends'])], 'label' => 'required|string|max:255', 'notes_md' => 'nullable|string|max:10000']);
    }
}
