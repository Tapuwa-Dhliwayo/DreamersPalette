<?php

namespace App\Policies;

use App\Models\ReviewGrant;
use App\Models\User;

class ReviewGrantPolicy
{
    public function view(User $user, ReviewGrant $grant): bool
    {
        if ($user->id === $grant->manuscript->author_id) {
            return $user->can('view', $grant->manuscript);
        }

        return $user->can('reviews.view') && $user->id === $grant->reviewer_id && $grant->isActive();
    }

    public function comment(User $user, ReviewGrant $grant): bool
    {
        return ($user->id === $grant->reviewer_id || $user->id === $grant->manuscript->author_id) && $grant->isActive() && $user->can('reviews.comment');
    }

    public function suggest(User $user, ReviewGrant $grant): bool
    {
        return $user->id === $grant->reviewer_id && $this->comment($user, $grant) && $grant->can_suggest && $user->can('reviews.suggest');
    }

    public function manage(User $user, ReviewGrant $grant): bool
    {
        return $user->id === $grant->manuscript->author_id && $user->can('update', $grant->manuscript);
    }
}
