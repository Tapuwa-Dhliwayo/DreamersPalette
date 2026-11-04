<?php

namespace App\Livewire\Forms\Worlds;

use Illuminate\Validation\Rule;
use Livewire\Form;

class ConversationForm extends Form
{
    public string $content = '';

    public string $mode = 'conversation';

    public ?int $connection_id = null;

    public function fields(): array
    {
        return $this->validate(['content' => 'required|string|max:30000', 'mode' => ['required', Rule::in(['conversation', 'collect', 'naming', 'summary'])], 'connection_id' => 'required|integer|min:1']);
    }
}
