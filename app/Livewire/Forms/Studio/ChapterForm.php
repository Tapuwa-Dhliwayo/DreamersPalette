<?php

namespace App\Livewire\Forms\Studio;

use Illuminate\Support\Facades\Validator;
use Livewire\Form;

class ChapterForm extends Form
{
    public string $title = '';

    public string $content_md = '';

    public string $summary_md = '';

    public int $revision = 1;

    public int $draft_number = 1;

    public string $request_uuid = '';

    public function validateWriting(array $writing): array
    {
        // Validate the complete request before filling typed state, including intentionally empty prose.
        $fields = Validator::make($writing, $this->rules())->validate();
        $this->fill($fields);
        $this->reset();

        return $fields;
    }

    public function rules(): array
    {
        $bytes = fn (int $limit) => function ($attribute, $writing, $fail) use ($limit): void {
            if (strlen($writing) > $limit) {
                $fail('This section is too large to save. Download your local copy and split it into smaller chapters.');
            }
        };

        return [
            'title' => ['required', 'string', 'max:255'],
            'content_md' => ['bail', 'present', 'string', $bytes(1048576)],
            'summary_md' => ['bail', 'present', 'string', $bytes(50000)],
            'revision' => ['required', 'integer', 'min:1'],
            'draft_number' => ['required', 'integer', 'min:1'],
            'request_uuid' => ['required', 'uuid'],
        ];
    }
}
