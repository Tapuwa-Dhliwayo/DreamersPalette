<?php

namespace App\Livewire\Forms\Studio;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

class AttachWorldForm extends Form
{
    public int $world_id = 0;

    public int $revision = 0;

    public int $draft_number = 0;

    public function selection(): array
    {
        return $this->validate([
            'world_id' => ['required', 'integer', Rule::exists('studio_worlds', 'id')->where('author_id', Auth::id())->whereNull('deleted_at')],
            'revision' => ['required', 'integer', 'min:1'],
            'draft_number' => ['required', 'integer', 'min:1'],
        ]);
    }
}
