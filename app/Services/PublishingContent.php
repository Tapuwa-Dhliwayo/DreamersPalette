<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Poem;
use App\Models\PoetryCollection;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PublishingContent
{
    public function save(Model $content, User $author, array $fields): Model
    {
        $creating = ! $content->exists;
        $expectedToken = $fields['edit_token'] ?? null;
        $publishing = $fields['is_published'] ?? null;
        unset($fields['edit_token'], $fields['is_published']);
        try {
            return DB::transaction(function () use ($content, $author, $fields, $creating, $expectedToken, $publishing): Model {
                $parent = $this->lockParent($content, $fields);
                if (! $creating) {
                    $content = $content->newQuery()->lockForUpdate()->findOrFail($content->id);
                    Gate::forUser($author)->authorize('update', $content);
                    if (! is_string($expectedToken) || ! hash_equals($content->editToken(), $expectedToken)) {
                        throw ValidationException::withMessages(['edit_token' => 'This record has newer saved changes. Your writing is still here. Reopen the latest version and compare it before saving.']);
                    }
                }
                if ($creating) {
                    Gate::forUser($author)->authorize('create', $content::class);
                    $content->author_id = $author->id;
                }
                if ($publishing !== null && (bool) $publishing !== (bool) $content->is_published) {
                    Gate::forUser($author)->authorize('publish', $content);
                }
                $this->requireEditableBook($content, $fields, $publishing);
                if ($parent) {
                    $this->assignParent($content, $parent, $author);
                }
                $content->fill($fields);
                if ($content instanceof Poem || $content instanceof Chapter) {
                    $content->content_md ??= '';
                }
                if ($content instanceof Chapter && Chapter::withTrashed()->where('book_id', $content->book_id)->where('chapter_number', $content->chapter_number)->when(! $creating, fn ($query) => $query->whereKeyNot($content->id))->exists()) {
                    throw ValidationException::withMessages(['chapter_number' => 'This chapter number is already used, including archived chapters.']);
                }
                if ($creating && ! $content instanceof Chapter) {
                    $content->assignSlug();
                }
                if ($publishing !== null) {
                    $content->is_published = (bool) $publishing;
                }
                $content->save();

                return $content;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['title' => 'A conflicting record was saved. Please retry or choose another chapter number.']);
        }
    }

    private function lockParent(Model $content, array $fields): ?Model
    {
        [$parentClass, $parentField] = match (true) {
            $content instanceof Poem => [PoetryCollection::class, 'collection_id'],
            $content instanceof Chapter => [Book::class, 'book_id'],
            default => [null, null],
        };
        if (! $parentClass) {
            return null;
        }
        $parentId = $fields[$parentField] ?? $content->$parentField;
        $parents = $parentClass::withTrashed()->whereIn('id', array_filter([$content->$parentField, $parentId]))->orderBy('id')->lockForUpdate()->get();
        if ($content instanceof Chapter && $parents->contains(fn ($book) => $book->isStudioManaged())) {
            throw ValidationException::withMessages(['content_md' => 'This novel is managed in the writing studio. Edit its manuscript and publish a finished draft.']);
        }
        $parent = $parents->find($parentId);
        if (! $parent || $parent->trashed()) {
            throw ValidationException::withMessages([$parentField => 'Choose an active parent belonging to the same author.']);
        }

        return $parent;
    }

    private function assignParent(Model $content, Model $parent, User $author): void
    {
        $parentField = match (true) {
            $content instanceof Poem => 'collection_id',
            $content instanceof Chapter => 'book_id',
        };
        if ($parent->author_id !== $content->author_id) {
            throw ValidationException::withMessages([$parentField => 'Choose an active parent belonging to the same author.']);
        }
        Gate::forUser($author)->authorize('update', $parent);
        $content->$parentField = $parent->id;
    }

    private function requireEditableBook(Model $content, array $fields, ?bool $publishing): void
    {
        if (! $content instanceof Book || ! $content->exists || ! $content->isStudioManaged()) {
            return;
        }
        $changesTitle = array_key_exists('title', $fields) && $fields['title'] !== $content->title;
        $changesSynopsis = array_key_exists('synopsis', $fields) && ($fields['synopsis'] ?? '') !== ($content->synopsis ?? '');
        if ($changesTitle || $changesSynopsis || ($publishing && ! $content->is_published)) {
            throw ValidationException::withMessages(['title' => 'This novel is managed in the writing studio. Publish a finished draft to change its public writing. Theme changes are still available here.']);
        }
    }
}
