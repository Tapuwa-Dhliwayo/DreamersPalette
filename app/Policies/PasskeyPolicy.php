<?php

namespace App\Policies;

use App\Models\User;
use Laravel\Passkeys\Passkey;

class PasskeyPolicy
{
    public function delete(User $user, Passkey $passkey): bool
    {
        return $user->can('account.security.manage') && $user->id === $passkey->user_id;
    }
}
