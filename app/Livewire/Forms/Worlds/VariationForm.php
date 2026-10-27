<?php

namespace App\Livewire\Forms\Worlds;

use App\Models\MagicVariationMode;
use Livewire\Form;

class VariationForm extends Form
{
    public int $mode_id = 0;

    public string $text_md = '';

    public string $reason_md = '';

    public int $revision = 0;

    public int $system_revision = 1;

    public int $manuscript_revision = 1;

    public int $draft_number = 1;

    public function rules(): array
    {
        return ['mode_id' => ['required', 'integer', 'min:0'], 'text_md' => ['present', 'string', 'max:12000'], 'reason_md' => ['required_unless:mode_id,0', 'string', 'max:12000'], 'revision' => ['required', 'integer', 'min:0'], 'system_revision' => ['required', 'integer', 'min:1'], 'manuscript_revision' => ['required', 'integer', 'min:1'], 'draft_number' => ['required', 'integer', 'min:1']];
    }

    public function writing(): array
    {
        $rules = $this->rules();
        if ($this->mode_id !== 0) {
            $rules['mode_id'][] = 'exists:studio_magic_variation_modes,id';
            $rules['text_md'][] = 'required_unless:mode_id,'.MagicVariationMode::where('key', 'exclude')->value('id');
        }

        return $this->validate($rules);
    }
}
