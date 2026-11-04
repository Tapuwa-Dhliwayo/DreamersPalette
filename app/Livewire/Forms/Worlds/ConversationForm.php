<?php

namespace App\Livewire\Forms\Worlds;

use Illuminate\Validation\Rule;
use Livewire\Form;

class ConversationForm extends Form
{
    public string $content = '';

    public string $reading_depth = 'focused';

    public string $model = '';

    public string $effort = 'default';

    public bool $auto_continue = true;

    public string $mode = 'conversation';

    public ?int $connection_id = null;

    public function fields(): array
    {
        return $this->validate(['model' => ['nullable', 'string', 'max:150', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9._:\\/-]*$/'], 'effort' => 'required|in:default,low,medium,high', 'auto_continue' => 'boolean', 'content' => 'required|string|max:30000', 'mode' => ['required', Rule::in(['conversation', 'collect', 'naming', 'summary'])], 'reading_depth' => ['required', Rule::in(['focused', 'whole'])], 'connection_id' => 'required|integer|min:1']);
    }
}
