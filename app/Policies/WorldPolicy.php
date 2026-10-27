<?php

namespace App\Policies;

use App\Models\User;
use App\Models\World;

class WorldPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('worlds.view');
    }

    public function create(User $user): bool
    {
        return $user->can('worlds.create');
    }

    public function view(User $user, World $world): bool
    {
        return ! $world->trashed() && $user->id === $world->author_id && $user->can('worlds.view');
    }

    public function update(User $user, World $world): bool
    {
        return ! $world->trashed() && $user->id === $world->author_id && $user->can('worlds.update');
    }

    public function restore(User $user, World $world): bool
    {
        return $user->id === $world->author_id && $user->can('worlds.update');
    }

    public function archive(User $user, World $world): bool
    {
        return $this->restore($user, $world);
    }
}
