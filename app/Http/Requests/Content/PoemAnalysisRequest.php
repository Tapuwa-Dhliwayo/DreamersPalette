<?php

namespace App\Http\Requests\Content;

use App\Services\Exchange\ExchangePackage;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PoemAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('poems.update') ?? false;
    }

    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'send' => [
                'content' => ['required', 'string', 'max:12000'],
                'connection_id' => ['required', 'integer'],
                'model' => ['required', 'string', 'max:200'],
                'effort' => ['required', 'string', 'max:30'],
                'request_uuid' => ['required', 'uuid'],
                'source' => ['required', Rule::in(['latest', 'previous'])],
                'fingerprint' => ['required', 'string', 'size:64'],
                'comparison_ids' => ['present', 'array', 'max:4'],
                'comparison_ids.*' => ['integer', 'distinct'],
            ],
            'retry' => ['request_uuid' => ['required', 'uuid']],
            'create' => ['title' => ['required', 'string', 'max:150']],
            'note' => [
                'content' => ['required', 'string', 'max:4000'],
                'source_message_id' => ['nullable', 'integer'],
                'note_id' => ['nullable', 'integer'],
            ],
            'previewImport' => [
                'markdown' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                    if (strlen($value) > ExchangePackage::MAX_FILE_BYTES) {
                        $fail('The file is larger than '.(ExchangePackage::MAX_FILE_BYTES / 1048576).' MiB.');
                    }
                }],
            ],
            default => [],
        };
    }
}
