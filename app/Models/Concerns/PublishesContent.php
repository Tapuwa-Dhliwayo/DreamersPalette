<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait PublishesContent
{
    protected static function bootPublishesContent(): void
    {
        static::deleting(function ($content): void {
            if (! $content->isForceDeleting() && $content->is_published) {
                $content->forceFill(['is_published' => false])->saveQuietly();
            }
        });

        static::restoring(function ($content): void {
            $content->is_published = false;
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->published();
    }

    public function isPublished(): bool
    {
        return $this->is_published && ! $this->trashed();
    }

    public function isPubliclyVisible(): bool
    {
        return $this->isPublished();
    }

    public function publish(): bool
    {
        if ($this->trashed()) {
            return false;
        }

        return $this->forceFill(['is_published' => true])->save();
    }

    public function unpublish(): bool
    {
        return $this->forceFill(['is_published' => false])->save();
    }

    public function archive(): bool
    {
        if ($this->trashed()) {
            return false;
        }

        return $this->getConnection()->transaction(fn () => (bool) $this->delete());
    }

    public function restoreAsDraft(): bool
    {
        if (! $this->trashed()) {
            return false;
        }

        return $this->restore();
    }
}
