<?php

namespace App\Models;

use App\Models\Concerns\HasPreciseTimestamps;
use App\Models\Concerns\PublishesContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chapter extends Model
{
    use HasPreciseTimestamps, PublishesContent, SoftDeletes;

    protected $hidden = ['source_uuid'];

    protected $fillable = ['title', 'chapter_number', 'content_md', 'is_preview'];

    protected function casts(): array
    {
        return [
            'author_id' => 'integer',
            'book_id' => 'integer',
            'chapter_number' => 'integer',
            'is_preview' => 'boolean',
            'is_published' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    public function isPubliclyVisible(): bool
    {
        return $this->isPublished() && (bool) $this->book?->isPublished();
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->published()->whereHas('book', fn (Builder $books) => $books->published());
    }
}
