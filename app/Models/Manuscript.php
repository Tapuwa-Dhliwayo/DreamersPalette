<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Manuscript extends Model
{
    use ArchivesStudioRecords;

    protected $table = 'studio_manuscripts';

    protected $fillable = ['title', 'description'];

    protected function casts(): array
    {
        return ['author_id' => 'integer', 'world_id' => 'integer', 'working_draft_number' => 'integer', 'revision' => 'integer', 'based_on_draft_id' => 'integer', 'compile_preferences' => 'array'];
    }

    protected function afterArchiveChange(int $authorId): void
    {
        $this->chapters()->withTrashed()->update(['revision' => DB::raw('revision + 1'), 'last_save_uuid' => null]);
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function linkWorld(World $world, int $expectedRevision, int $expectedDraft): void
    {
        $this->getConnection()->transaction(function () use ($world, $expectedRevision, $expectedDraft): void {
            $manuscript = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            $manuscript->requireActiveDraft($expectedDraft);
            if ($manuscript->revision !== $expectedRevision || ($manuscript->world_id && $manuscript->world_id !== $world->id)) {
                throw ValidationException::withMessages(['linkForm.manuscript_id' => 'The manuscript changed or already belongs to another world. Reopen this form.']);
            }
            if ($manuscript->world_id === $world->id) {
                return;
            }
            $manuscript->forceFill(['world_id' => $world->id, 'revision' => $manuscript->revision + 1])->save();
        });
    }

    public function updateDetails(array $fields, int $revision): void
    {
        $this->getConnection()->transaction(function () use ($fields, $revision): void {
            $manuscript = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if ($manuscript->revision !== $revision) {
                throw ValidationException::withMessages(['conflict' => 'The manuscript changed. Reopen details before saving.']);
            }
            $manuscript->fill($fields);
            $manuscript->revision++;
            $manuscript->save();
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(ManuscriptChapter::class)->orderBy('position')->orderBy('id');
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(ManuscriptDraft::class)->whereNull('undone_at')->orderByDesc('number');
    }

    public function addChapter(User $author, int $expectedDraft, ?int $expectedRevision = null): ManuscriptChapter
    {
        return $this->getConnection()->transaction(function () use ($author, $expectedDraft, $expectedRevision): ManuscriptChapter {
            $manuscript = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            $manuscript->requireActiveDraft($expectedDraft);
            if ($expectedRevision !== null && $manuscript->revision !== $expectedRevision) {
                throw ValidationException::withMessages(['structure' => 'The manuscript changed. Refresh before adding a chapter.']);
            }
            $chapter = $manuscript->chapters()->make([
                'title' => 'Untitled chapter', 'content_md' => '',
            ]);
            $chapter->position = (int) $manuscript->chapters()->withTrashed()->max('position') + 1;
            $chapter->updated_by = $author->id;
            $chapter->save();
            $manuscript->increment('revision');

            return $chapter->refresh();
        });
    }

    public function setChapterArchived(int $id, bool $archive, int $draftNumber, int $revision, User $author): void
    {
        $this->getConnection()->transaction(function () use ($id, $archive, $draftNumber, $revision, $author): void {
            $manuscript = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            $manuscript->requireActiveDraft($draftNumber);
            if ($manuscript->revision !== $revision) {
                throw ValidationException::withMessages(['structure' => 'The manuscript changed. Refresh before archiving or restoring a chapter.']);
            }
            $chapter = $manuscript->chapters()->withTrashed()->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($chapter->trashed() === $archive) {
                return;
            }
            $chapter->forceFill(['deleted_at' => $archive ? now() : null, 'archived_by' => $archive ? $author->id : null, 'revision' => $chapter->revision + 1, 'last_save_uuid' => null])->save();
            $manuscript->increment('revision');
        });
    }

    public function moveChapter(int $chapterId, string $direction, int $expectedRevision): void
    {
        $this->getConnection()->transaction(function () use ($chapterId, $direction, $expectedRevision): void {
            $manuscript = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if ($manuscript->revision !== $expectedRevision) {
                throw ValidationException::withMessages(['structure' => 'The manuscript changed in another session. Refresh the chapter list before moving it.']);
            }
            $chapters = $manuscript->chapters()->get();
            $index = $chapters->search(fn ($chapter) => $chapter->id === $chapterId);
            abort_if($index === false, 404);
            $neighbour = $chapters->get($index + ($direction === 'up' ? -1 : 1));
            if (! $neighbour) {
                return;
            }
            $chapter = $chapters[$index];
            $position = $chapter->position;
            $chapter->position = $neighbour->position;
            $neighbour->position = $position;
            $chapter->save();
            $neighbour->save();
            $manuscript->increment('revision');
        });
    }

    public function canUndoFinish(ManuscriptDraft $draft): bool
    {
        return $draft->manuscript_id === $this->id
            && $draft->undone_at === null
            && $this->based_on_draft_id === $draft->id
            && $this->working_draft_number === $draft->number + 1
            && $this->revision === $draft->source_revision + 1;
    }

    public function requireActiveDraft(int $expectedDraft): void
    {
        if ($this->working_draft_number !== $expectedDraft) {
            throw ValidationException::withMessages(['conflict' => 'This draft was finished in another session. Your local writing is retained. Reload before working on the next draft.']);
        }
    }
}
