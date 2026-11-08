<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

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

    public function editToken(): string
    {
        $fields = array_unique([...$this->getFillable(), 'id', 'author_id', 'collection_id', 'book_id', 'slug', 'chapter_number', 'is_preview', 'is_published', 'published_at', 'deleted_at', 'created_at', 'updated_at']);
        $state = collect($this->getRawOriginal())->only($fields)->sortKeys()->all();

        return hash('sha256', json_encode($state, JSON_THROW_ON_ERROR));
    }

    public function assignSlug(string $fallback = 'untitled'): void
    {
        $base = substr(Str::slug($this->title), 0, 240) ?: $fallback;
        $slug = $base;
        $suffix = 1;
        while ($this->newQuery()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }
        $this->slug = $slug;
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
