<?php

namespace App\Models;

use App\Models\Concerns\HasPreciseTimestamps;
use App\Models\Concerns\PublishesContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Poem extends Model
{
    use HasPreciseTimestamps, PublishesContent, SoftDeletes;

    protected $hidden = ['source_uuid'];

    protected $fillable = ['title', 'content_md', 'excerpt'];

    protected function casts(): array
    {
        return [
            'author_id' => 'integer',
            'collection_id' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(PoetryCollection::class, 'collection_id');
    }

    protected static function booted(): void
    {
        static::saving(function (Poem $poem): void {
            if ($poem->is_published && $poem->published_at === null) {
                $poem->published_at = now();
            }
        });
    }

    public function isPubliclyVisible(): bool
    {
        return $this->isPublished() && (bool) $this->collection?->isPublished();
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->published()->whereHas('collection', fn (Builder $collections) => $collections->published());
    }
}
