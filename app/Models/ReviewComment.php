<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewComment extends Model
{
    protected $table = 'studio_review_comments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['accepted_at' => 'immutable_datetime', 'dismissed_at' => 'immutable_datetime'];
    }

    public function dismiss(): void
    {
        DB::transaction(function (): void {
            $comment = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if ($comment->accepted_at) {
                throw ValidationException::withMessages(['conflict' => 'This suggestion was already applied. It cannot be dismissed.']);
            }
            if (! $comment->dismissed_at) {
                $comment->update(['dismissed_at' => now()]);
            }
        });
    }

    public function accept(ReviewGrant $grant, User $author, int $revision): void
    {
        DB::transaction(function () use ($grant, $author, $revision): void {
            $manuscript = Manuscript::whereKey($grant->manuscript_id)->lockForUpdate()->firstOrFail();
            $comment = static::where('grant_id', $grant->id)->lockForUpdate()->findOrFail($this->id);
            if ($comment->accepted_at) {
                return;
            }
            if ($comment->dismissed_at || $comment->suggested_md === null) {
                throw ValidationException::withMessages(['conflict' => 'This suggestion is no longer open.']);
            }
            $source = $grant->draft->chapters()->findOrFail($comment->chapter_id);
            $chapter = $manuscript->chapters()->lockForUpdate()->findOrFail($source->source_chapter_id);
            if ($manuscript->revision !== $revision || $chapter->content_md !== $source->content_md) {
                throw ValidationException::withMessages(['conflict' => 'Current writing differs from the reviewed chapter. Compare and merge the suggestion manually; nothing was replaced.']);
            }
            $chapter->forceFill(['content_md' => $comment->suggested_md, 'updated_by' => $author->id, 'revision' => $chapter->revision + 1, 'last_save_uuid' => null])->save();
            $manuscript->increment('revision');
            $comment->update(['accepted_at' => now(), 'accepted_by' => $author->id]);
        });
    }
}
