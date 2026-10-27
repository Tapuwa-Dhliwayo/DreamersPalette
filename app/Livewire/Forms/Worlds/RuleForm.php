<?php

namespace App\Livewire\Forms\Worlds;

use Livewire\Form;

class RuleForm extends Form
{
    public string $title = '';

    public string $statement_md = '';

    public string $conditions_md = '';

    public string $consequences_md = '';

    public string $exceptions_md = '';

    public string $reasoning_md = '';

    public int $decision_id = 1;

    public int $revision = 1;

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'statement_md' => ['required', 'string', 'max:12000'], 'conditions_md' => ['present', 'string', 'max:12000'], 'consequences_md' => ['present', 'string', 'max:12000'], 'exceptions_md' => ['present', 'string', 'max:12000'], 'reasoning_md' => ['present', 'string', 'max:12000'], 'decision_id' => ['required', 'integer', 'exists:studio_magic_decisions,id'], 'revision' => ['required', 'integer', 'min:1']];
    }
}
