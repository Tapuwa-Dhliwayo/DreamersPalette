<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $actor, User $user): bool
    {
        return $actor->can('account.view') && ($actor->id === $user->id || $actor->can('users.manage'));
    }

    public function updateProfile(User $actor, User $user): bool
    {
        return $actor->id === $user->id && $actor->can('account.view');
    }

    public function manageSecurity(User $actor, User $user): bool
    {
        return $actor->id === $user->id && $actor->can('account.security.manage');
    }
}
