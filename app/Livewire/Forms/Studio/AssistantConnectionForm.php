<?php

namespace App\Livewire\Forms\Studio;

use App\Services\Worldbuilding\AssistantProviders;
use Illuminate\Validation\Rule;
use Livewire\Form;

class AssistantConnectionForm extends Form
{
    public string $name = '';

    public string $provider = 'openai';

    public string $model = '';

    public string $credential = '';

    public bool $enabled = true;

    public int $daily_request_limit = 200;

    public int $turn_request_limit = 100;

    public ?string $input_rate_usd = null;

    public ?string $output_rate_usd = null;

    public int $revision = 1;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'provider' => ['required', Rule::in(array_keys(AssistantProviders::PROVIDERS))],
            'model' => ['required', 'string', 'max:200', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9_\.\/:\-]*$/D'],
            'credential' => 'nullable|string|max:4000',
            'input_rate_usd' => 'nullable|numeric|min:0|max:100000',
            'output_rate_usd' => 'nullable|numeric|min:0|max:100000',
            'enabled' => 'boolean',
            'daily_request_limit' => 'required|integer|between:1,10000',
            'turn_request_limit' => 'required|integer|between:1,1000',
        ];
    }

    public function discoveryFields(): array
    {
        return $this->validate(collect($this->rules())->only(['provider', 'credential'])->all());
    }

    public function fields(): array
    {
        return $this->validate();
    }
}
