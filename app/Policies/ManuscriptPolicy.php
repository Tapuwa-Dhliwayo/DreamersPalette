<?php

namespace App\Policies;

use App\Models\Manuscript;
use App\Models\User;

class ManuscriptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manuscripts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('manuscripts.create');
    }

    public function view(User $user, Manuscript $manuscript): bool
    {
        return ! $manuscript->trashed() && $user->id === $manuscript->author_id && $user->can('manuscripts.view');
    }

    public function update(User $user, Manuscript $manuscript): bool
    {
        return ! $manuscript->trashed() && $user->id === $manuscript->author_id && $user->can('manuscripts.update');
    }

    public function finish(User $user, Manuscript $manuscript): bool
    {
        return ! $manuscript->trashed() && $user->id === $manuscript->author_id && $user->can('manuscripts.finish');
    }

    public function restoreDraft(User $user, Manuscript $manuscript): bool
    {
        return $this->finish($user, $manuscript) && $this->update($user, $manuscript);
    }

    public function undoFinish(User $user, Manuscript $manuscript): bool
    {
        return $this->finish($user, $manuscript) && $this->update($user, $manuscript);
    }

    public function export(User $user, Manuscript $manuscript): bool
    {
        return ! $manuscript->trashed() && $user->id === $manuscript->author_id && $user->can('manuscripts.export');
    }

    public function restore(User $user, Manuscript $manuscript): bool
    {
        return $user->id === $manuscript->author_id && $user->can('manuscripts.update');
    }

    public function archive(User $user, Manuscript $manuscript): bool
    {
        return $this->restore($user, $manuscript);
    }
}
