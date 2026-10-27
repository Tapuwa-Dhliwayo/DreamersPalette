<?php

namespace App\Livewire\Forms\Studio;

use Illuminate\Validation\Rule;
use Livewire\Form;

class CompileForm extends Form
{
    public string $source = 'working';

    public string $format = 'markdown';

    public array $chapters = [];

    public bool $prose = true;

    public bool $summaries = false;

    public bool $world = false;

    public int $revision = 0;

    public function fields(): array
    {
        return $this->validate([
            'source' => ['required', 'regex:/^(working|draft:[1-9][0-9]*)$/'],
            'format' => ['required', Rule::in(['markdown', 'pdf', 'review'])],
            'chapters' => ['required', 'array', 'list', 'min:1', 'max:1000'],
            'chapters.*' => ['required', 'integer', 'min:1', 'distinct'],
            'prose' => ['boolean', function ($attribute, $included, $fail): void {
                if (! $included && ! $this->summaries && ! ($this->format === 'review' && $this->world)) {
                    $fail('Include the writing, chapter summaries, or both.');
                }
            }],
            'summaries' => ['boolean'], 'world' => ['boolean'],
            'revision' => ['required', 'integer', 'min:1'],
        ], [
            'chapters.required' => 'Select at least one chapter.',
            'chapters.min' => 'Select at least one chapter.',
            'chapters.*.distinct' => 'Each chapter can be included only once.',
        ]);
    }
}
