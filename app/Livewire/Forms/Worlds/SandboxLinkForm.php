<?php

namespace App\Livewire\Forms\Worlds;

use App\Services\Worldbuilding\ProposalTypes;
use Illuminate\Validation\Rule;
use Livewire\Form;

class SandboxLinkForm extends Form
{
    public string $source = '';

    public string $target = '';

    public string $kind = 'connection';

    public string $label = '';

    public string $notes_md = '';

    public function fields(): array
    {
        $fields = $this->validate(['source' => 'required|string|max:80|different:target', 'target' => 'required|string|max:80', 'kind' => ['required', Rule::in(['connection', 'before', 'participant', 'relationship', 'depends'])], 'label' => 'required|string|max:255', 'notes_md' => 'nullable|string|max:10000']);
        $endpoints = [];
        foreach (['source', 'target'] as $side) {
            if (ctype_digit($fields[$side]) && (int) $fields[$side] > 0) {
                $endpoints[$side.'_id'] = (int) $fields[$side];
                $endpoints[$side.'_key'] = null;

                continue;
            }
            if (! preg_match('/^(person|entry|system|event):[1-9][0-9]*$/', $fields[$side])) {
                ProposalTypes::invalid('Choose a board piece or established subject.');
            }
            $endpoints[$side.'_id'] = null;
            $endpoints[$side.'_key'] = $fields[$side];
        }
        if (! $endpoints['source_id'] && ! $endpoints['target_id']) {
            ProposalTypes::invalid('Connect at least one board piece. Use Relationships for two established subjects.');
        }

        return $endpoints + collect($fields)->only(['kind', 'label', 'notes_md'])->all();
    }
}
