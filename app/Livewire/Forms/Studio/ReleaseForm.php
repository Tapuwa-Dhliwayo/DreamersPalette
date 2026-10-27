<?php

namespace App\Livewire\Forms\Studio;

use Livewire\Form;

class ReleaseForm extends Form
{
    public ?int $draft_id = null;

    public int $revision = 0;

    public string $public_synopsis = '';

    public bool $confirmed = false;

    public function rules(): array
    {
        return ['draft_id' => ['required', 'integer', 'min:1'], 'revision' => ['required', 'integer', 'min:1'], 'confirmed' => ['accepted'], 'public_synopsis' => ['nullable', 'string', 'max:65535']];
    }
}
