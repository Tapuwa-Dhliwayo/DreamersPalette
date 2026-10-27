<?php

namespace App\Livewire\Forms\Worlds;

use Livewire\Form;

class EntryForm extends Form
{
    public bool $character = false;

    public string $name = '';

    public int $type_id = 0;

    public string $aliases = '';

    public string $notes_md = '';

    public int $revision = 0;

    public function writing(bool $allowCharacter = false): array
    {
        $fields = $this->validate(['name' => ['required', 'string', 'max:255'], 'type_id' => $allowCharacter && $this->character ? ['exclude'] : ['required', 'integer', 'exists:studio_entry_types,id'], 'aliases' => ['nullable', 'string', 'max:5000'], 'notes_md' => ['nullable', 'string', 'max:200000'], 'revision' => ['required', 'integer', 'min:0']]);
        $fields['aliases'] = array_values(array_unique(array_filter(array_map('trim', preg_split('/\R/', $fields['aliases'])))));
        unset($fields['revision']);

        return $fields;
    }
}
