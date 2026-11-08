<?php

namespace App\Policies;

use App\Models\PoemAnalysisConversation;
use App\Models\User;

class PoemAnalysisConversationPolicy
{
    public function update(User $user, PoemAnalysisConversation $conversation): bool
    {
        return ! $conversation->trashed()
            && ! $conversation->poem->trashed()
            && $conversation->author_id === $user->id
            && $conversation->poem->author_id === $user->id
            && $user->can('poems.update');
    }
}
