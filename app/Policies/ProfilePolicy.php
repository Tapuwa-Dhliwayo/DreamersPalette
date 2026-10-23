<?php

namespace App\Policies;

use App\Models\Profile;
use App\Models\User;

class ProfilePolicy
{
    public function view(User $user, Profile $profile): bool
    {
        return $user->can('account.view') && ($user->id === $profile->user_id || $user->can('content.manage-all'));
    }

    public function update(User $user, Profile $profile): bool
    {
        return $user->can('profiles.update') && ($user->id === $profile->user_id || $user->can('content.manage-all'));
    }
}
