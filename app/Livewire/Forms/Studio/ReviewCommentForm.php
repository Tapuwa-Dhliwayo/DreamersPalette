<?php

namespace App\Livewire\Forms\Studio;

use Livewire\Form;

class ReviewCommentForm extends Form
{
    public string $body_md = '';

    public bool $suggesting = false;

    public string $suggested_md = '';

    public function rules(): array
    {
        return ['body_md' => ['required', 'string', 'max:50000'], 'suggesting' => ['boolean'], 'suggested_md' => ['present', 'string', function ($attribute, $text, $fail): void {
            if (strlen($text) > 1048576) {
                $fail('The proposed chapter exceeds the writing limit of 1 MiB.');
            }
        }]];
    }
}
