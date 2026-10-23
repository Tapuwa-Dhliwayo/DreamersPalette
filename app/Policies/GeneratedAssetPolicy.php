<?php

namespace App\Policies;

use App\Models\GeneratedAsset;
use App\Models\User;

class GeneratedAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('generated-assets.view');
    }

    public function view(User $user, GeneratedAsset $asset): bool
    {
        return $user->can('generated-assets.view') && ($user->id === $asset->author_id || $user->can('content.manage-all'));
    }

    public function create(User $user): bool
    {
        return $user->can('generated-assets.create');
    }

    public function delete(User $user, GeneratedAsset $asset): bool
    {
        return $user->can('generated-assets.delete') && ($user->id === $asset->author_id || $user->can('content.manage-all'));
    }
}
