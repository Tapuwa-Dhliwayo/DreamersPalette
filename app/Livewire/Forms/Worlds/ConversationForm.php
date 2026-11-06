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

    public ?int $connection_id = null;

    public function fields(): array
    {
        return $this->validate([
            'model' => ['nullable', 'string', 'max:200', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9._:\\/-]*$/'],
            'effort' => 'required|in:default,none,minimal,low,medium,high,xhigh,max,ultra',
            'auto_continue' => 'boolean',
            'content' => 'required|string|max:30000',
            'reading_depth' => ['required', Rule::in(['focused', 'whole'])],
            'connection_id' => 'required|integer|min:1',
        ]);
    }
}
