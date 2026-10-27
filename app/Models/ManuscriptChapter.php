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
            $chapter->fill(collect($writing)->only(['title', 'content_md', 'summary_md'])->all());
            $chapter->revision++;
            $chapter->last_save_uuid = $writing['request_uuid'];
            $chapter->updated_by = $author->id;
            $chapter->save();
            $manuscript->increment('revision');

            return $chapter;
        });
    }
}
