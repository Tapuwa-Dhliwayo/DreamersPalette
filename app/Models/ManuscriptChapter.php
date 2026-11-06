<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class ManuscriptChapter extends Model
{
    use SoftDeletes;

    protected $table = 'studio_chapters';

    protected $fillable = ['title', 'content_md', 'summary_md'];

    protected function casts(): array
    {
        return ['manuscript_id' => 'integer', 'revision' => 'integer', 'position' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $chapter): void {
            if ($chapter->isDirty('content_md')) {
                $chapter->word_count = WritingActivity::wordCount($chapter->content_md ?? '');
            }
        });
    }

    public function readingPassages(): array
    {
        $paragraphs = preg_split('/\\R\\s*\\R/u', trim($this->content_md ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $passages = [];
        foreach ($paragraphs as $paragraph) {
            // Very large pasted paragraphs still need bounded, stable reading ranges.
            while (mb_strlen($paragraph) > 4000) {
                $boundary = mb_strrpos(mb_substr($paragraph, 0, 4000), ' ');
                $boundary = $boundary && $boundary > 2000 ? $boundary : 4000;
                $passages[] = mb_substr($paragraph, 0, $boundary);
                $paragraph = mb_substr($paragraph, $boundary);
            }
            if (trim($paragraph) !== '') {
                $passages[] = $paragraph;
            }
        }

        return $passages;
    }

    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class);
    }

    public function saveWriting(array $writing, User $author): self
    {
        return $this->getConnection()->transaction(function () use ($writing, $author): self {
            $manuscript = Manuscript::whereKey($this->manuscript_id)->lockForUpdate()->firstOrFail();
            $chapter = $manuscript->chapters()->whereKey($this->id)->lockForUpdate()->firstOrFail();
            $manuscript->requireActiveDraft($writing['draft_number']);
            if ($chapter->last_save_uuid === $writing['request_uuid']) {
                $same = $chapter->title === $writing['title'] && $chapter->content_md === $writing['content_md']
                    && ($chapter->summary_md ?? '') === ($writing['summary_md'] ?? '');
                if (! $same) {
                    throw ValidationException::withMessages(['conflict' => 'A save identifier was reused with different writing. Reload and compare your local copy.']);
                }

                return $chapter;
            }
            if ($chapter->revision !== $writing['revision']) {
                throw ValidationException::withMessages(['conflict' => 'This chapter has newer server writing. Your local copy is retained. Reload to compare both versions.']);
            }
            $before = $chapter->content_md ?? '';
            $chapter->fill(collect($writing)->only(['title', 'content_md', 'summary_md'])->all());
            $chapter->revision++;
            $chapter->last_save_uuid = $writing['request_uuid'];
            $chapter->updated_by = $author->id;
            $chapter->save();
            $manuscript->increment('revision');
            if ($before !== $chapter->content_md) {
                $manuscript->recordWritingActivity('writing', $author->id, $chapter->id, WritingActivity::changes($before, $chapter->content_md));
            }

            return $chapter;
        });
    }
}
