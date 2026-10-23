<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('generated-assets.create');
    }

    public function rules(): array
    {
        return ['prompt' => ['required', 'string', 'max:500'], 'type' => ['required', Rule::in(['background', 'cover'])]];
    }
}
