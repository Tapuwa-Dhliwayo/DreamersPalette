<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Poem;
use App\Models\PoetryCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReaderController extends Controller
{
    private function query(string $resource)
    {
        $query = ContentController::modelClass($resource)::publiclyVisible();
        if ($resource === 'poems') {
            $query->with('collection');
        }
        if ($resource === 'chapters') {
            $query->with('book');
        }

        return $query;
    }

    private function present($content): array
    {
        $fields = $content->toArray();
        unset($fields['ai_prompt']);
        if ($content instanceof Poem) {
            $fields['poetry_collections'] = $fields['collection'];
            unset($fields['collection']);
        }
        if ($content instanceof Chapter) {
            $fields['books'] = $fields['book'];
            unset($fields['books']['ai_prompt'], $fields['book']);
        }

        return $fields;
    }

    public function index(Request $request, string $resource)
    {
        $query = $this->query($resource);
        if (in_array($resource, ['poems', 'chapters'])) {
            $query->select($resource === 'poems'
                ? ['id', 'collection_id', 'author_id', 'title', 'slug', 'excerpt', 'created_at', 'published_at']
                : ['id', 'book_id', 'title', 'chapter_number', 'reading_position', 'is_preview']);
        }
        if ($resource === 'poems' && $request->filled('collection')) {
            $query->whereHas('collection', fn ($parent) => $parent->where('slug', $request->query('collection')));
        }
        if ($resource === 'chapters' && $request->filled('book')) {
            $query->whereHas('book', fn ($parent) => $parent->where('slug', $request->query('book')));
        }
        $ascending = $resource === 'chapters' || ($resource === 'poems' && $request->filled('collection'));
        if ($resource === 'chapters') {
            $query->orderByRaw('COALESCE(reading_position, chapter_number)');
        }
        $query->orderBy($resource === 'chapters' ? 'chapter_number' : 'created_at', $ascending ? 'asc' : 'desc')->orderBy('id');
        if ($request->has('page')) {
            $page = max(1, (int) $request->query('page', 1));
            $size = min(100, max(1, (int) $request->query('pageSize', 12)));
            $count = (clone $query)->count();

            return response()->json(['data' => $query->forPage($page, $size)->get()->map(fn ($content) => $this->present($content)), 'count' => $count]);
        }
        $query->limit(min(1000, max(1, (int) $request->query('limit', 1000))));

        return response()->json($query->get()->map(fn ($content) => $this->present($content)));
    }

    public function show(Request $request, string $resource, string $slug)
    {
        $query = $this->query($resource);
        if ($resource === 'chapters') {
            $query->whereHas('book', fn ($parent) => $parent->where('slug', $request->query('book')))->where('chapter_number', $slug);
        } else {
            $query->where($request->boolean('by_id') ? 'id' : 'slug', $slug);
        }

        return response()->json($this->present($query->firstOrFail()));
    }

    public function previewPoems(Request $request, string $slug)
    {
        $collection = PoetryCollection::where('slug', $slug)->firstOrFail();
        Gate::authorize('preview', $collection);
        $poems = $collection->poems()->published()->orderBy('created_at')->orderBy('id');
        $count = (clone $poems)->count();
        $page = max(1, (int) $request->query('page', 1));
        $size = min(100, max(1, (int) $request->query('pageSize', 12)));

        return response()->json(['data' => $poems->forPage($page, $size)->get(['id', 'title', 'slug', 'excerpt']), 'count' => $count]);
    }

    public function preview(Request $request, string $resource, string $slug)
    {
        abort_unless(in_array($resource, ['collections', 'books']), 404);
        $content = ContentController::modelClass($resource)::where('slug', $slug)->firstOrFail();
        Gate::authorize('preview', $content);

        return response()->json($content);
    }
}
