<?php

namespace App\Livewire\Forms\Account;

use Livewire\Form;

class ProfileForm extends Form
{
    public string $display_name = '';

    public string $bio = '';

    public $avatar;

    public bool $remove_avatar = false;

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['boolean'],
        ];
    }

    public function fields(): array
    {
        $this->display_name = trim($this->display_name);
        $this->bio = trim($this->bio);

        return $this->validate();
    }
}
