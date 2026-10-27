<?php

namespace App\Livewire\Forms\Studio;

use Livewire\Form;

class AdoptBookForm extends Form
{
    public ?int $book_id = null;

    public function rules(): array
    {
        return ['book_id' => ['required', 'integer', 'min:1']];
    }
}
