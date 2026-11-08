<?php

namespace App\Policies;

use App\Models\AssistantConnection;
use App\Models\User;

class AssistantConnectionPolicy
{
    public function update(User $user, AssistantConnection $connection): bool
    {
        return $connection->user_id === $user->id && $user->canAny(['worlds.update', 'poems.update']);
    }
}
