<?php

namespace App\Livewire\Forms\Worlds;

use App\Models\World;
use Livewire\Form;

class AlignmentForm extends Form
{
    public string $title = '';

    public string $left_label = 'Chaotic';

    public string $right_label = 'Lawful';

    public string $top_label = 'Good';

    public string $bottom_label = 'Evil';

    public string $notes_md = '';

    public int $revision = 0;

    public function writing(World $world): array
    {
        return $this->validate(['title' => ['required', 'string', 'max:255'], 'left_label' => ['required', 'string', 'max:80'], 'right_label' => ['required', 'string', 'max:80'], 'top_label' => ['required', 'string', 'max:80'], 'bottom_label' => ['required', 'string', 'max:80'], 'notes_md' => ['nullable', 'string', 'max:200000'], 'revision' => ['required', 'integer', 'min:0']]);
    }
}
