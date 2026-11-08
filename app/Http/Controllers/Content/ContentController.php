<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\PublishContentRequest;
use App\Http\Requests\Content\SaveContentRequest;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\Poem;
use App\Models\PoetryCollection;
use App\Services\PublishingContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

    private function studioLinks(Model $content, Request $request): Model
    {
        $content->setAttribute('edit_token', $content->editToken());
        $content->setAttribute('capabilities', [
            'update' => $request->user()->can('update', $content)
                && match (true) {
                    $content instanceof Poem => $request->user()->can('collections.update'),
                    $content instanceof Chapter => $request->user()->can('books.update'),
                    default => true,
                },
            'publish' => $request->user()->can('publish', $content),
            'archive' => $request->user()->can('delete', $content),
        ]);
        $book = $content instanceof Book ? $content : ($content instanceof Chapter ? $content->book : null);
        if (! $book) {
            return $content;
        }
        $manuscript = $book->manuscript;
        if ($manuscript) {
            $capabilities = $content->getAttribute('capabilities');
            if ($content instanceof Chapter) {
                $capabilities = ['update' => false, 'publish' => false, 'archive' => false];
            }
            if ($content instanceof Book && ! $content->is_published) {
                $capabilities['publish'] = false;
            }
            $content->setAttribute('capabilities', $capabilities);
        }
        $owned = $manuscript && $manuscript->author_id === $request->user()->id && $request->user()->can('manuscripts.view');
        $content->setAttribute('is_studio_managed', $manuscript !== null);
        $content->setAttribute('studio_manuscript_id', $owned ? $manuscript->id : null);
        $content->setAttribute('studio_manuscript_archived', $owned && $manuscript->trashed());
        if ($content instanceof Book) {
            $content->unsetRelation('manuscript');
        }
        if ($content instanceof Chapter) {
            $content->unsetRelation('book');
        }

        return $content;
    }

    public function index(Request $request, string $resource)
    {
        Gate::authorize('viewAny', self::modelClass($resource));
        $query = $this->owned($request, $resource);
        if ($resource === 'books') {
            $query->with('manuscript:id,book_id,author_id,deleted_at');
        }
        if ($resource === 'chapters') {
            $query->with(['book' => fn ($books) => $books->withTrashed()->with('manuscript:id,book_id,author_id,deleted_at')]);
        }

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

        return response()->json($query->orderBy($resource === 'chapters' ? 'chapter_number' : 'created_at', $resource === 'chapters' ? 'asc' : 'desc')->get()->map(fn ($content) => $this->studioLinks($content, $request)));
    }

    public function show(Request $request, string $resource, string $id)
    {
        $content = $this->owned($request, $resource)->findOrFail($id);
        Gate::authorize('view', $content);

        return response()->json($this->studioLinks($content, $request));
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
        $content = app(PublishingContent::class)->save($content, $request->user(), $request->validated());

        return response()->json($this->studioLinks($content->refresh(), $request), $creating ? 201 : 200);
    }

    private function requireUnmanagedChapter(Model $content): void
    {
        if ($content instanceof Chapter && Book::withTrashed()->whereKey($content->book_id)->lockForUpdate()->first()?->isStudioManaged()) {
            throw ValidationException::withMessages(['content_md' => 'Manage chapters through this novel’s writing studio.']);
        }
    }

    public function publish(PublishContentRequest $request, string $resource, string $id)
    {
        $content = $this->owned($request, $resource)->findOrFail($id);
        Gate::authorize('publish', $content);
        DB::transaction(function () use ($content, $request): void {
            $this->requireUnmanagedChapter($content);
            if ($content instanceof Book) {
                Book::whereKey($content->id)->lockForUpdate()->firstOrFail();
                if ($content->isStudioManaged() && $request->boolean('is_published')) {
                    throw ValidationException::withMessages(['is_published' => 'Publish this novel through its writing studio.']);
                }
            }
            $request->validated()['is_published'] ? $content->publish() : $content->unpublish();
        });

        return response()->json($content);
    }

    public function archive(Request $request, string $resource, string $id)
    {
        $content = $this->owned($request, $resource)->findOrFail($id);
        Gate::authorize('delete', $content);
        DB::transaction(function () use ($content): void {
            $this->requireUnmanagedChapter($content);
            $content->archive();
        });

        return response()->json($content);
    }

    public function restore(Request $request, string $resource, string $id)
    {
        $content = $this->owned($request, $resource)->onlyTrashed()->findOrFail($id);
        Gate::authorize('restore', $content);
        DB::transaction(function () use ($content): void {
            $this->requireUnmanagedChapter($content);
            $content->restoreAsDraft();
        });

        return response()->json($content);
    }
}
