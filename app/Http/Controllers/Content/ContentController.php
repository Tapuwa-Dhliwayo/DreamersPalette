<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\PublishContentRequest;
use App\Http\Requests\Content\SaveContentRequest;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\Poem;
use App\Models\PoetryCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContentController extends Controller
{
    public static function modelClass(string $resource): string
    {
        return match ($resource) {
            'collections' => PoetryCollection::class,
            'poems' => Poem::class,
            'books' => Book::class,
            'chapters' => Chapter::class,
            default => abort(404),
        };
    }

    private function owned(Request $request, string $resource): Builder
    {
        $query = self::modelClass($resource)::query();
        if (! $request->user()->can('content.manage-all')) {
            $query->where('author_id', $request->user()->id);
        }

        return $query;
    }

    public function index(Request $request, string $resource)
    {
        Gate::authorize('viewAny', self::modelClass($resource));
        $query = $this->owned($request, $resource);
        if ($request->boolean('archived')) {
            $query->onlyTrashed();
        }
        foreach (['collection_id', 'book_id'] as $parent) {
            if (($resource === 'poems' && $parent === 'collection_id' || $resource === 'chapters' && $parent === 'book_id') && $request->filled($parent)) {
                $query->where($parent, $request->query($parent));
            }
        }
        if ($resource === 'collections') {
            $query->withCount(['poems', 'poems as published_poems_count' => fn ($poems) => $poems->published()]);
        }

        return response()->json($query->orderBy($resource === 'chapters' ? 'chapter_number' : 'created_at', $resource === 'chapters' ? 'asc' : 'desc')->get());
    }

    public function show(Request $request, string $resource, string $id)
    {
        $content = $this->owned($request, $resource)->findOrFail($id);
        Gate::authorize('view', $content);

        return response()->json($content);
    }

    public function store(SaveContentRequest $request, string $resource)
    {
        Gate::authorize('create', self::modelClass($resource));

        return $this->save($request, $resource, new (self::modelClass($resource)));
    }

    public function update(SaveContentRequest $request, string $resource, string $id)
    {
        $content = $this->owned($request, $resource)->findOrFail($id);
        Gate::authorize('update', $content);

        return $this->save($request, $resource, $content);
    }

    private function save(SaveContentRequest $request, string $resource, Model $content)
    {
        $creating = ! $content->exists;
        $fields = $request->validated();
        $publishing = $fields['is_published'] ?? null;
        unset($fields['is_published']);
        if ($publishing !== null && (bool) $publishing !== (bool) $content->is_published) {
            Gate::authorize('publish', $content->exists ? $content : tap($content, fn ($record) => $record->author_id = $request->user()->id));
        }
        try {
            DB::transaction(function () use ($request, $resource, $content, $fields, $creating, $publishing): void {
                $content->fill($fields);
                if ($creating) {
                    $content->author_id = $request->user()->id;
                }
                if (in_array($resource, ['poems', 'chapters'])) {
                    $parentField = $resource === 'poems' ? 'collection_id' : 'book_id';
                    $parentClass = $resource === 'poems' ? PoetryCollection::class : Book::class;
                    $parentId = $fields[$parentField] ?? $content->$parentField;
                    $parent = $parentClass::query()->lockForUpdate()->find($parentId);
                    if (! $parent || $parent->author_id !== $content->author_id) {
                        throw ValidationException::withMessages([$parentField => 'Choose an active parent belonging to the same author.']);
                    }
                    Gate::authorize('update', $parent);
                    $content->$parentField = $parentId;
                    $content->content_md ??= '';
                }
                if ($resource === 'chapters' && Chapter::withTrashed()->where('book_id', $content->book_id)->where('chapter_number', $content->chapter_number)->when(! $creating, fn ($query) => $query->whereKeyNot($content->id))->exists()) {
                    throw ValidationException::withMessages(['chapter_number' => 'This chapter number is already used, including archived chapters.']);
                }
                if ($creating && $resource !== 'chapters') {
                    $base = substr(Str::slug($content->title), 0, 240) ?: 'untitled';
                    $slug = $base;
                    $suffix = 1;
                    while ($content->newQuery()->withTrashed()->where('slug', $slug)->exists()) {
                        $slug = $base.'-'.$suffix++;
                    }
                    $content->slug = $slug;
                }
                if ($publishing !== null) {
                    $content->is_published = (bool) $publishing;
                }
                $content->save();
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['title' => 'A conflicting record was saved. Please retry or choose another chapter number.']);
        }

        return response()->json($content->refresh(), $creating ? 201 : 200);
    }

    public function publish(PublishContentRequest $request, string $resource, string $id)
    {
        $content = $this->owned($request, $resource)->findOrFail($id);
        Gate::authorize('publish', $content);
        $fields = $request->validated();
        $fields['is_published'] ? $content->publish() : $content->unpublish();

        return response()->json($content);
    }

    public function archive(Request $request, string $resource, string $id)
    {
        $content = $this->owned($request, $resource)->findOrFail($id);
        Gate::authorize('delete', $content);
        $content->archive();

        return response()->json($content);
    }

    public function restore(Request $request, string $resource, string $id)
    {
        $content = $this->owned($request, $resource)->onlyTrashed()->findOrFail($id);
        Gate::authorize('restore', $content);
        $content->restoreAsDraft();

        return response()->json($content);
    }
}
