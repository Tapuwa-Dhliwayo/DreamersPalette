<?php

namespace App\Livewire\Forms\Worlds;

use Livewire\Form;

class PositionForm extends Form
{
    public int $x = 0;

    public int $y = 0;

    public int $revision = 0;

    public function coordinates(): array
    {
        return $this->validate([
            'x' => ['required', 'integer', 'between:-100,100'],
            'y' => ['required', 'integer', 'between:-100,100'],
            'revision' => ['required', 'integer', 'min:1'],
        ]);
    }
}
