<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorldExercise;

class WorldExercisePolicy
{
    public function view(User $user, WorldExercise $exercise): bool
    {
        return ! $exercise->trashed() && $user->can('view', $exercise->world);
    }

    public function update(User $user, WorldExercise $exercise): bool
    {
        return ! $exercise->trashed() && $user->can('update', $exercise->world);
    }
}
