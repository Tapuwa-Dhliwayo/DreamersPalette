<?php

namespace App\Livewire\Forms\Studio;

use Livewire\Form;

class FinishDraftForm extends Form
{
    public string $name = '';

    public string $note = '';

    public int $revision = 1;

    public int $draft_number = 1;

    public string $request_uuid = '';

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:160'], 'note' => ['nullable', 'string', 'max:10000'],
            'revision' => ['required', 'integer', 'min:1'], 'draft_number' => ['required', 'integer', 'min:1'],
            'request_uuid' => ['required', 'uuid']];
    }
}
