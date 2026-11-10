<?php

namespace App\Livewire\Forms\Worlds;

use App\Services\Exchange\ExchangePackage;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class ImportForm extends Form
{
    public $upload;

    public string $pasted = '';

    public bool $conflictsAsAlternatives = false;

    public function markdown(): string
    {
        $this->validate([
            'upload' => ['nullable', 'file', 'extensions:md,markdown,txt', 'max:'.intdiv(ExchangePackage::MAX_FILE_BYTES, 1024)],
            'pasted' => ['nullable', 'string', 'max:'.ExchangePackage::MAX_FILE_BYTES],
        ]);
        $hasUpload = $this->upload !== null;
        $hasPasted = trim($this->pasted) !== '';
        if ($hasUpload === $hasPasted) {
            throw ValidationException::withMessages(['importForm.pasted' => $hasUpload ? 'Use either a file or pasted text, not both.' : 'Choose a file or paste the Markdown from the chat.']);
        }

        return $hasUpload ? $this->upload->get() : $this->pasted;
    }

    // The package is kept only in the previewed import's private file, never in component state.
    public function clearPackage(): void
    {
        $this->upload?->delete();
        $this->upload = null;
        $this->pasted = '';
    }
}
