<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class ContentPolicy
{
    protected string $resource;

    public function viewAny(User $user): bool
    {
        return $user->can($this->resource.'.view');
    }

    public function view(?User $user, Model $content): bool
    {
        if ($content->isPubliclyVisible()) {
            return true;
        }

        return $user !== null && $this->allows($user, $content, 'view');
    }

    public function create(User $user): bool
    {
        return $user->can($this->resource.'.create');
    }

    public function update(User $user, Model $content): bool
    {
        return ! $content->trashed() && $this->allows($user, $content, 'update');
    }

    public function publish(User $user, Model $content): bool
    {
        return ! $content->trashed() && $this->allows($user, $content, 'publish');
    }

    public function delete(User $user, Model $content): bool
    {
        return ! $content->trashed() && $this->allows($user, $content, 'archive');
    }

    public function restore(User $user, Model $content): bool
    {
        return $content->trashed() && $this->allows($user, $content, 'restore');
    }

    public function preview(User $user, Model $content): bool
    {
        return ! $content->trashed() && $this->allows($user, $content, 'preview');
    }

    public function forceDelete(User $user, Model $content): bool
    {
        return false;
    }

    private function allows(User $user, Model $content, string $ability): bool
    {
        return $user->can($this->resource.'.'.$ability)
            && ($user->id === $content->author_id || $user->can('content.manage-all'));
    }
}
