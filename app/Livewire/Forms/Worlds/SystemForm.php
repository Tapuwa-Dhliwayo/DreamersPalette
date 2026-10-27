<?php

namespace App\Livewire\Forms\Worlds;

use Illuminate\Validation\ValidationException;
use Livewire\Form;

class SystemForm extends Form
{
    public string $name = '';

    public string $notes_md = '';

    public string $aliases = '';

    public int $revision = 1;

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'notes_md' => ['present', 'string', 'max:200000'], 'aliases' => ['nullable', 'string', 'max:6000'], 'revision' => ['required', 'integer', 'min:1']];
    }

    public function writing(): array
    {
        $fields = $this->validate();
        $aliases = array_values(array_unique(array_filter(array_map('trim', preg_split('/\R/u', $this->aliases)))));
        if (count($aliases) > 50 || collect($aliases)->contains(fn ($alias) => mb_strlen($alias) > 120)) {
            throw ValidationException::withMessages(['systemForm.aliases' => 'Use up to 50 aliases, each at most 120 characters.']);
        }
        unset($fields['revision']);
        $fields['aliases'] = $aliases;

        return $fields;
    }
}
