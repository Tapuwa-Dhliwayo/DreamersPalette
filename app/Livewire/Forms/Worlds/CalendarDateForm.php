<?php

namespace App\Livewire\Forms\Worlds;

use Livewire\Form;

class CalendarDateForm extends Form
{
    public int $year = 1;

    public int $month = 1;

    public int $day = 1;

    public ?int $event_id = null;

    public function fields(): array
    {
        return $this->validate(['year' => ['required', 'integer', 'between:1,1000000'], 'month' => ['required', 'integer', 'between:1,60'], 'day' => ['required', 'integer', 'between:1,2000'], 'event_id' => ['nullable', 'integer', 'min:1']]);
    }
}
