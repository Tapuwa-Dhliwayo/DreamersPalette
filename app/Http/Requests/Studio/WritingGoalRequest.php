<?php

namespace App\Http\Requests\Studio;

use Illuminate\Foundation\Http\FormRequest;

class WritingGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('manuscript'));
    }

    public function rules(): array
    {
        return ['weekly_word_goal' => ['nullable', 'integer', 'min:1', 'max:1000000']];
    }
}
