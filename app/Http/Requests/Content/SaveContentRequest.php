<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Resource policies are enforced by the controller.
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->title)) {
            $this->merge(['title' => trim($this->title)]);
        }
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';
        $rules = ['title' => [$required, 'required', 'string', 'max:255'], 'is_published' => ['sometimes', 'boolean']];
        $resource = $this->route('resource');
        if (in_array($resource, ['collections', 'books'])) {
            $rules += [
                'theme_background_url' => ['nullable', 'string', 'max:2048', 'regex:~^(?:/storage/(?:imports/supabase/|uploads/|generated/)[^\s<>]*|https://[^\s<>]+)$~'],
                'theme_overlay_opacity' => ['nullable', 'numeric', 'between:0,1'],
                'theme_text_mode' => ['nullable', Rule::in(['light', 'dark'])],
                'accent_color' => ['nullable', 'regex:/^#[a-fA-F0-9]{6}$/'],
            ];
        }
        if ($resource === 'collections') {
            $rules['description'] = ['nullable', 'string', 'max:65535'];
            foreach (['theme_text_color', 'theme_heading_color', 'theme_muted_color'] as $color) {
                $rules[$color] = ['nullable', 'regex:/^#[a-fA-F0-9]{6}$/'];
            }
        }
        if ($resource === 'books') {
            $rules += ['synopsis' => ['nullable', 'string', 'max:65535'], 'ai_prompt' => ['nullable', 'string', 'max:500'], 'cover_image_url' => ['nullable', 'string', 'max:2048', 'regex:~^(?:/storage/(?:imports/supabase/|uploads/|generated/)[^\s<>]*|https://[^\s<>]+)$~']];
        }
        if (in_array($resource, ['poems', 'chapters'])) {
            $rules['content_md'] = [$this->isMethod('post') ? 'present' : 'sometimes', 'nullable', 'string', 'max:1000000'];
        }
        if ($resource === 'poems') {
            $rules += ['collection_id' => [$required, 'uuid'], 'excerpt' => ['nullable', 'string', 'max:65535']];
        }
        if ($resource === 'chapters') {
            $rules += ['book_id' => [$required, 'uuid'], 'chapter_number' => [$required, 'integer', 'min:1', 'max:2147483647'], 'is_preview' => ['sometimes', 'boolean']];
        }

        return $rules;
    }
}
