<?php

namespace App\Livewire\Forms\Studio;

use Illuminate\Validation\ValidationException;
use Livewire\Form;

class ReferenceForm extends Form
{
    public int $chapter_id = 0;

    public int $source_revision = 0;

    public int $draft_number = 0;

    public string $wording = '';

    public string $prefix = '';

    public string $suffix = '';

    public function selection(): array
    {
        return $this->validate(['chapter_id' => ['required', 'integer', 'min:1'], 'source_revision' => ['required', 'integer', 'min:1'], 'draft_number' => ['required', 'integer', 'min:1'], 'wording' => ['required', 'string', 'max:2000'], 'prefix' => ['present', 'string', 'max:256'], 'suffix' => ['present', 'string', 'max:256']]);
    }

    public function aliasesFor(string $kind, array $aliases, string $name): array
    {
        $this->validateOnly('wording', ['wording' => ['required', 'string', 'max:'.($kind === 'system' ? 120 : 255)]]);
        if (mb_strtolower($name) === mb_strtolower($this->wording) || collect($aliases)->contains(fn ($alias) => mb_strtolower($alias) === mb_strtolower($this->wording))) {
            return $aliases;
        }
        $aliases[] = $this->wording;
        if (($kind === 'system' && count($aliases) > 50) || mb_strlen(implode("\n", $aliases)) > 5000) {
            throw ValidationException::withMessages(['referenceForm.wording' => 'This entry has reached its alias limit. Edit its aliases before adding another.']);
        }

        return $aliases;
    }
}
