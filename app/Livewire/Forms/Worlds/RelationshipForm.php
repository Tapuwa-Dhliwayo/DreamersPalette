<?php

namespace App\Livewire\Forms\Worlds;

use App\Models\WorldRelationship;
use Illuminate\Validation\Rule;
use Livewire\Form;

class RelationshipForm extends Form
{
    public string $source_key = '';

    public string $target_key = '';

    public string $label = '';

    public string $period = '';

    public string $notes_md = '';

    public bool $is_dependency = false;

    public bool $is_mutual = false;

    public int $revision = 1;

    public function setRelationship(WorldRelationship $relationship): void
    {
        $this->fill([
            'source_key' => $relationship->source_key,
            'target_key' => $relationship->target_key,
            'label' => $relationship->label,
            'period' => $relationship->period ?? '',
            'notes_md' => $relationship->notes_md ?? '',
            'is_dependency' => $relationship->is_dependency,
            'is_mutual' => $relationship->is_mutual,
            'revision' => $relationship->revision,
        ]);
    }

    public function fields(): array
    {
        return $this->validate([
            'source_key' => ['required', 'max:40', 'regex:/^(person|entry|system):[1-9][0-9]*$/'],
            'target_key' => ['required', 'max:40', Rule::notIn([$this->source_key]), 'regex:/^(person|entry|system):[1-9][0-9]*$/'],
            'label' => ['required', 'string', 'max:255'],
            'is_mutual' => ['boolean'],
            'is_dependency' => ['boolean', function ($attribute, $enabled, $fail): void {
                if ($enabled && $this->is_mutual) {
                    $fail('A dependency has a direction. Turn off the mutual relationship option to include this link in change impact.');
                }
            }],
            'period' => ['nullable', 'string', 'max:255'],
            'notes_md' => ['nullable', 'string', 'max:100000'],
        ]);
    }
}
