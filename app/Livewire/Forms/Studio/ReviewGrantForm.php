<?php

namespace App\Livewire\Forms\Studio;

use Livewire\Form;

class ReviewGrantForm extends Form
{
    public string $email = '';

    public ?int $draft_id = null;

    public bool $can_suggest = false;

    public int $days = 14;

    public function rules(): array
    {
        return ['email' => ['required', 'email', 'max:255'], 'draft_id' => ['required', 'integer', 'min:1'], 'can_suggest' => ['boolean'], 'days' => ['required', 'integer', 'between:1,90']];
    }
}
