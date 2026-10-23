<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class WritingAssistantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('poems.update') || $this->user()->can('chapters.update');
    }

    public function rules(): array
    {
        return ['sentence' => ['required', 'string', 'max:1000']];
    }
}
