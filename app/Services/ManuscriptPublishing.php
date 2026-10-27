<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Manuscript;
use App\Models\ManuscriptRelease;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManuscriptPublishing
{
    public function adopt(Book $book, User $author): Manuscript
    {
        Gate::authorize('create', Manuscript::class);
        Gate::authorize('update', $book);
        abort_unless($book->author_id === $author->id, 403);

        return DB::transaction(function () use ($book, $author): Manuscript {
            $book = Book::whereKey($book->id)->lockForUpdate()->firstOrFail();
            $existing = Manuscript::withTrashed()->where('book_id', $book->id)->first();
            if ($existing) {
                if ($existing->trashed()) {
                    throw ValidationException::withMessages(['adoption.book_id' => 'This novel already has an archived manuscript. Restore it from the manuscript library.']);
                }

                return $existing;
            }
            $manuscript = new Manuscript(['title' => $book->title, 'description' => $book->synopsis]);
            $manuscript->forceFill(['author_id' => $author->id, 'book_id' => $book->id])->save();
            foreach ($book->chapters()->withTrashed()->orderBy('chapter_number')->get() as $index => $source) {
                abort_unless($source->author_id === $author->id, 403);
                $chapter = $manuscript->chapters()->make($source->only(['title', 'content_md']));
                $chapter->forceFill(['position' => $index + 1, 'public_chapter_id' => $source->id, 'updated_by' => $author->id, 'deleted_at' => $source->deleted_at, 'archived_by' => $source->trashed() ? $author->id : null])->save();
            }
            $this->captureRelease($manuscript, $book, null, $author, (string) Str::uuid());

            return $manuscript;
        });
    }

    public function publish(Manuscript $manuscript, int $draftId, int $revision, User $author, string $requestUuid, ?string $synopsis = null): Book
    {
        Gate::authorize('finish', $manuscript);

        return DB::transaction(function () use ($manuscript, $draftId, $revision, $author, $requestUuid, $synopsis): Book {
            $manuscript = Manuscript::whereKey($manuscript->id)->lockForUpdate()->firstOrFail();
            $draft = $manuscript->drafts()->with('chapters')->findOrFail($draftId);
            $prior = ManuscriptRelease::where('request_uuid', $requestUuid)->first();
            if ($prior) {
                abort_unless((int) $prior->manuscript_id === $manuscript->id && (int) $prior->draft_id === $draftId, 409);
                $book = Book::findOrFail($prior->book_id);
                Gate::authorize('publish', $book);

                return $book;
            }
            if ($manuscript->revision !== $revision) {
                throw ValidationException::withMessages(['release.draft_id' => 'The manuscript changed. Reopen publishing and review the source again.']);
            }
            if ($draft->chapters->isEmpty()) {
                throw ValidationException::withMessages(['release.draft_id' => 'Choose a finished draft containing chapters.']);
            }
            $book = $manuscript->book_id ? Book::whereKey($manuscript->book_id)->lockForUpdate()->firstOrFail() : new Book(['title' => $draft->manuscript_title]);
            if (! $book->exists) {
                Gate::authorize('create', Book::class);
                $slug = substr(Str::slug($draft->manuscript_title), 0, 240) ?: 'novel';
                $candidate = $slug;
                $suffix = 1;
                while (Book::withTrashed()->where('slug', $candidate)->exists()) {
                    $candidate = $slug.'-'.$suffix++;
                }
                $book->forceFill(['author_id' => $author->id, 'slug' => $candidate]);
            }
            abort_unless($book->author_id === $author->id && $manuscript->author_id === $author->id, 403);
            Gate::authorize('publish', $book);
            $book->forceFill(['title' => $draft->manuscript_title, 'synopsis' => $synopsis ?? $book->synopsis, 'is_published' => true])->save();
            $manuscript->forceFill(['book_id' => $book->id])->save();
            $released = [];
            $nextNumber = (int) $book->chapters()->withTrashed()->max('chapter_number') + 1;
            foreach ($draft->chapters as $position => $snapshot) {
                $working = $manuscript->chapters()->withTrashed()->findOrFail($snapshot->source_chapter_id);
                $public = $working->public_chapter_id ? $book->chapters()->withTrashed()->findOrFail($working->public_chapter_id) : new Chapter;
                if (! $public->exists) {
                    Gate::authorize('create', Chapter::class);
                }
                if (! $public->exists) {
                    $public->forceFill(['author_id' => $author->id, 'book_id' => $book->id, 'chapter_number' => $nextNumber++, 'is_preview' => true]);
                }
                Gate::authorize('publish', $public);
                $public->forceFill(['title' => $snapshot->title, 'content_md' => $snapshot->content_md, 'reading_position' => $position + 1, 'deleted_at' => null, 'is_published' => true])->save();
                $working->forceFill(['public_chapter_id' => $public->id])->save();
                $released[] = $public->id;
            }
            $book->chapters()->whereNotIn('id', $released)->update(['is_published' => false]);
            $this->captureRelease($manuscript, $book, $draftId, $author, $requestUuid);
            $manuscript->increment('revision');

            return $book;
        });
    }

    private function captureRelease(Manuscript $manuscript, Book $book, ?int $draftId, User $author, string $uuid): void
    {
        ManuscriptRelease::create(['book_id' => $book->id, 'manuscript_id' => $manuscript->id, 'draft_id' => $draftId,
            'number' => (int) ManuscriptRelease::where('book_id', $book->id)->max('number') + 1, 'request_uuid' => $uuid, 'created_by' => $author->id, 'created_at' => now(),
            'snapshot' => ['book' => $book->only(['title', 'slug', 'synopsis', 'is_published', 'cover_image_url', 'theme_background_url', 'theme_overlay_opacity', 'theme_text_mode', 'accent_color']),
                'chapters' => $book->chapters()->withTrashed()->orderByRaw('COALESCE(reading_position, chapter_number)')->get()->map(fn ($chapter) => $chapter->only(['id', 'chapter_number', 'reading_position', 'title', 'content_md', 'is_preview', 'is_published', 'deleted_at']))->all()]]);
    }
}
