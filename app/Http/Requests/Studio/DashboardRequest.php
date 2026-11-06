<?php

namespace App\Http\Requests\Studio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'days' => ['sometimes', 'integer', Rule::in([7, 30, 90])],
            'timezone' => ['sometimes', 'timezone'],
            'manuscript_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
