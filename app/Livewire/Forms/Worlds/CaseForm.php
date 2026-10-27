<?php

namespace App\Livewire\Forms\Worlds;

use App\Models\MagicSystem;
use Illuminate\Validation\Rule;
use Livewire\Form;

class CaseForm extends Form
{
    public string $title = '';

    public string $situation_md = '';

    public string $outcome_md = '';

    public string $reasoning_md = '';

    public array $rule_ids = [];

    public int $revision = 1;

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'situation_md' => ['required', 'string', 'max:12000'], 'outcome_md' => ['present', 'string', 'max:12000'], 'reasoning_md' => ['present', 'string', 'max:12000'], 'rule_ids' => ['array', 'max:100'], 'revision' => ['required', 'integer', 'min:1']];
    }

    public function writing(MagicSystem $system): array
    {
        return $this->validate([...$this->rules(), 'rule_ids.*' => ['integer', 'distinct', Rule::exists('studio_magic_rules', 'id')->where('system_id', $system->id)]]);
    }
}
