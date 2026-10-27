<?php

namespace App\Livewire\Forms\Worlds;

use Livewire\Form;

class CalendarForm extends Form
{
    public string $name = '';

    public array $months = [['name' => '', 'days' => 30]];

    public array $weekdays = [];

    public ?int $leap_every = null;

    public ?int $leap_month = null;

    public int $leap_days = 1;

    public string $notes_md = '';

    public int $revision = 1;

    public function fields(): array
    {
        return $this->validate(['name' => ['required', 'string', 'max:255'], 'months' => ['required', 'array', 'list', 'min:1', 'max:60'], 'months.*.name' => ['required', 'string', 'max:100', 'distinct'], 'months.*.days' => ['required', 'integer', 'between:1,1000'], 'weekdays' => ['array', 'list', 'max:30'], 'weekdays.*' => ['required', 'string', 'max:100', 'distinct'], 'leap_every' => ['nullable', 'integer', 'between:1,10000'], 'leap_month' => [$this->leap_every ? 'required' : 'nullable', 'integer', 'min:1', 'max:'.count($this->months)], 'leap_days' => ['required', 'integer', 'between:1,1000'], 'notes_md' => ['nullable', 'string', 'max:60000']]);
    }
}
