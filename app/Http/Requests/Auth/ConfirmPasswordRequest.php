<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageSecurity', $this->user()) ?? false;
    }

    public function rules(): array
    {
        return ['password' => ['required', 'string', 'current_password:web']];
    }
}
