<?php

namespace App\Livewire\Studio\Account;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.studio')]
#[Title('Account security')]
class Security extends Component
{
    public function render()
    {
        abort_unless(auth()->user()->can('account.security.manage'), 403);

        return view('livewire.studio.account.security');
    }
}
