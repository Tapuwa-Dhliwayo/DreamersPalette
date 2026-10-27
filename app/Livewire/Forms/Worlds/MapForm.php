<?php

namespace App\Livewire\Forms\Worlds;

use Livewire\Form;

class MapForm extends Form
{
    public string $title = '';

    public string $unit = 'km';

    public ?float $width_units = null;

    public int $width_px = 1200;

    public int $height_px = 800;

    public array $pins = [];

    public array $journey = [];

    public string $notes_md = '';

    public int $revision = 1;

    public $image;

    public function fields(): array
    {
        $fields = $this->validate([
            'title' => ['required', 'string', 'max:255'], 'unit' => ['required', 'string', 'max:40'],
            'width_units' => ['nullable', 'numeric', 'gt:0', 'max:1000000000000'],
            'width_px' => ['required', 'integer', 'min:100', 'max:20000'], 'height_px' => ['required', 'integer', 'min:100', 'max:20000'],
            'pins' => ['array', 'list', 'max:300'], 'pins.*.key' => ['required', 'uuid', 'distinct'],
            'pins.*.name' => ['required', 'string', 'max:255'], 'pins.*.entry_id' => ['nullable', 'integer', 'min:1'],
            'pins.*.x' => ['required', 'numeric', 'between:0,100'], 'pins.*.y' => ['required', 'numeric', 'between:0,100'],
            'journey' => ['array', 'list', 'max:500'], 'journey.*' => ['required', 'uuid'],
            'notes_md' => ['nullable', 'string', 'max:100000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:12288', 'dimensions:min_width=100,min_height=100,max_width=16000,max_height=16000'],
        ]);
        unset($fields['image']);
        $fields['pins'] = array_map(fn ($pin) => [...$pin, 'entry_id' => filled($pin['entry_id']) ? (int) $pin['entry_id'] : null], $fields['pins']);

        return $fields;
    }
}
