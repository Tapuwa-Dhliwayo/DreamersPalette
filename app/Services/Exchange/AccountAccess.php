<?php

namespace App\Services\Exchange;

use App\Models\AssistantMessage;
use App\Models\ExternalGrant;
use App\Models\Manuscript;
use App\Models\ManuscriptChapter;
use App\Models\Poem;
use App\Models\PoemAnalysisConversation;
use App\Models\PoemAnalysisSnapshot;
use App\Models\PoemAuthorNote;
use App\Models\PoetryCollection;
use App\Models\SandboxIdea;
use App\Models\User;
use App\Models\World;
use App\Models\WorldExercise;
use App\Services\Worldbuilding\ProposalTypes;
use App\Services\Worldbuilding\WorldContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Every read an external AI client can make. Each call re-checks, in order: verified email and
 * the external-access permission, the resource policy, then the author's grant. No grant and no
 * record look identical to the client.
 */
class AccountAccess
{
    public const MAX_RESULTS = 30;

    private const SEARCHABLE_WORLD_COLUMNS = ['notes_md', 'description_md', 'summary_md', 'truth_md', 'question_md', 'conclusion_md', 'domain'];

    public function __construct(private WorldContext $worlds) {}

    public function list(User $user): array
    {
        $this->assertConnected($user);
        $grants = $this->grants($user);
        $list = ['worlds' => [], 'exercises' => [], 'manuscripts' => [], 'collections' => [], 'poems' => []];
        foreach ($this->granted($user, 'world', World::class, 'view') as $world) {
            $workspace = $grants['world'][$world->id]->includes_workspace;
            $list['worlds'][] = ['id' => 'world:'.$world->id, 'title' => $world->name, 'summary' => str($world->description ?? '')->squish()->limit(140)->toString(), 'workspace_included' => $workspace];
            foreach ($workspace ? $world->exercises()->get() : [] as $exercise) {
                $list['exercises'][] = ['id' => 'exercise:'.$exercise->id, 'title' => $exercise->title, 'summary' => 'Sandbox exercise in '.$world->name];
            }
        }
        foreach ($this->granted($user, 'manuscript', Manuscript::class, 'view') as $manuscript) {
            $chapters = $manuscript->chapters()->get();
            $list['manuscripts'][] = ['id' => 'manuscript:'.$manuscript->id, 'title' => $manuscript->title, 'summary' => $chapters->count().' chapters, '.number_format($chapters->sum('word_count')).' words', 'workspace_included' => false];
        }
        foreach ($this->granted($user, 'collection', PoetryCollection::class, 'view') as $collection) {
            $list['collections'][] = ['id' => 'collection:'.$collection->id, 'title' => $collection->title, 'summary' => $collection->poems()->count().' poems', 'workspace_included' => $grants['collection'][$collection->id]->includes_workspace];
        }
        foreach ($this->readablePoems($user, $grants)->with('collection:id,title')->get() as $poem) {
            $list['poems'][] = ['id' => 'poem:'.$poem->id, 'title' => $poem->title, 'summary' => $poem->collection ? 'In '.$poem->collection->title : 'Uncollected', 'workspace_included' => $this->poemWorkspaceIncluded($poem, $grants)];
        }

        return $list;
    }

    public function fetch(User $user, string $ref, int $offset = 0, int $limit = 12000): array
    {
        $this->assertConnected($user);
        if (! preg_match('#^(world|manuscript|chapter|exercise|collection|poem|analysis):([1-9][0-9]{0,17})(?:/([a-z]+(?::[1-9][0-9]{0,17})?))?$#D', $ref, $parts)) {
            throw new InvalidArgumentException('Unknown reference. Use an id returned by list_material or search.');
        }
        [, $type, $id, $sub] = $parts + [3 => null];
        $id = (int) $id;
        $page = match ($type) {
            'world' => $sub === null ? $this->world($user, $id) : $this->worldRecord($user, $id, $sub),
            'manuscript' => $this->manuscript($user, $id),
            'chapter' => $this->chapter($user, $id),
            'exercise' => match ($sub) {
                null => $this->exercise($user, $id),
                'messages' => $this->exerciseMessages($user, $id),
                default => throw new ExternalMaterialUnavailable,
            },
            'collection' => $this->collection($user, $id),
            'poem' => $this->poem($user, $id),
            'analysis' => $this->analysis($user, $id),
        };

        return $this->paged($ref, $page, max(0, $offset), min(16000, max(100, $limit)));
    }

    public function search(User $user, string $query): array
    {
        $this->assertConnected($user);
        $query = trim($query);
        $length = mb_strlen($query);
        if ($length < 2 || $length > 200) {
            throw new InvalidArgumentException('Search text must be 2 to 200 characters.');
        }
        $like = '%'.addcslashes($query, '\\%_').'%';
        $grants = $this->grants($user);
        $hits = [
            ...$this->searchChapters($user, $like, $query),
            ...$this->searchWorldRecords($user, $grants, $like, $query),
            ...$this->searchPoems($user, $grants, $like, $query),
            ...$this->searchWorkspace($user, $grants, $like, $query),
        ];

        return array_slice($hits, 0, self::MAX_RESULTS);
    }

    /**
     * What this author may still choose to share: owned, not archived, and permitted by policy.
     *
     * @return array<string, array<int, string>> type => [id => label]
     */
    public function selectable(User $user): array
    {
        $labels = fn (string $model, string $label, string $ability): array => $model::where('author_id', $user->id)->orderBy($label)->get()
            ->filter(fn ($record) => Gate::forUser($user)->allows($ability, $record))->pluck($label, 'id')->all();

        return [
            'world' => $labels(World::class, 'name', 'view'),
            'manuscript' => $labels(Manuscript::class, 'title', 'view'),
            'collection' => $labels(PoetryCollection::class, 'title', 'view'),
            'poem' => Poem::where('author_id', $user->id)->whereNull('collection_id')->orderBy('title')->get()
                ->filter(fn ($poem) => Gate::forUser($user)->allows('view', $poem))->pluck('title', 'id')->all(),
        ];
    }

    private function assertConnected(User $user): void
    {
        if (! $user->hasVerifiedEmail() || ! $user->can('account.external-access')) {
            throw new ExternalMaterialUnavailable;
        }
    }

    /** @return array<string, array<int, ExternalGrant>> */
    private function grants(User $user): array
    {
        $grants = array_fill_keys(ExternalGrant::TYPES, []);
        foreach ($user->externalGrants()->get() as $grant) {
            $grants[$grant->resource_type][$grant->resource_id] = $grant;
        }

        return $grants;
    }

    /** Authorised, granted records of one type: policy view plus a grant, never either alone. */
    private function granted(User $user, string $type, string $model, string $ability)
    {
        $ids = array_keys($this->grants($user)[$type]);

        return $model::whereIn('id', $ids ?: [0])->where('author_id', $user->id)->orderBy('id')->get()
            ->filter(fn ($record) => Gate::forUser($user)->allows($ability, $record))->values();
    }

    private function readablePoems(User $user, array $grants)
    {
        return Poem::where('author_id', $user->id)->where(fn ($query) => $query
            ->whereIn('id', array_keys($grants['poem']) ?: [0])
            ->orWhereIn('collection_id', array_keys($grants['collection']) ?: [0]))->orderBy('id');
    }

    private function poemWorkspaceIncluded(Poem $poem, array $grants): bool
    {
        return (bool) (($grants['poem'][$poem->id] ?? null)?->includes_workspace
            || ($grants['collection'][$poem->collection_id] ?? null)?->includes_workspace);
    }

    private function resolve(User $user, string $type, string $model, int $id, string $ability = 'view'): array
    {
        $record = $model::find($id);
        $grant = $this->grants($user)[$type][$id] ?? null;
        if (! $record || ! $grant || $record->author_id !== $user->id || ! Gate::forUser($user)->allows($ability, $record)) {
            throw new ExternalMaterialUnavailable;
        }

        return [$record, $grant];
    }

    private function accessiblePoem(User $user, int $id): array
    {
        $poem = Poem::with('collection')->find($id);
        $grants = $this->grants($user);
        $granted = isset($grants['poem'][$id]) || isset($grants['collection'][$poem?->collection_id]);
        if (! $poem || ! $granted || $poem->author_id !== $user->id || ! Gate::forUser($user)->allows('view', $poem)
            || ($poem->collection && ! Gate::forUser($user)->allows('view', $poem->collection))) {
            throw new ExternalMaterialUnavailable;
        }

        return [$poem, $this->poemWorkspaceIncluded($poem, $grants)];
    }

    private function world(User $user, int $id): array
    {
        [$world, $grant] = $this->resolve($user, 'world', World::class, $id);
        $records = collect($this->worlds->catalogue($world, true))->map(fn (array $record): array => ['ref' => 'world:'.$world->id.'/'.$record['key']] + $record)->values()->all();

        return $this->page($world->name, $this->json(['world' => ['id' => $world->id, 'name' => $world->name, 'description' => $world->description], 'workspace_included' => $grant->includes_workspace, 'records' => $records]),
            route('studio.world', $world), ['type' => 'world', 'state' => 'current', 'revision' => null]);
    }

    private function worldRecord(User $user, int $id, string $key): array
    {
        [$world] = $this->resolve($user, 'world', World::class, $id);
        try {
            $record = $this->worlds->target($world, $key, true);
        } catch (ValidationException|ModelNotFoundException) {
            throw new ExternalMaterialUnavailable;
        }
        $snapshot = $this->worlds->snapshot($record, true);
        unset($snapshot['image_path']);
        $title = (string) ($record->name ?? $record->title ?? $record->label);

        return $this->page($title, $this->json(['target_key' => $key, 'revision' => $record->revision, 'archived' => $record->trashed(), 'record' => $snapshot]),
            $this->worlds->url($world, $key), ['type' => explode(':', $key)[0], 'revision' => $record->revision, 'state' => $record->trashed() ? 'archived' : 'current', 'target_key' => $key]);
    }

    private function manuscript(User $user, int $id): array
    {
        [$manuscript] = $this->resolve($user, 'manuscript', Manuscript::class, $id);
        $chapters = $manuscript->chapters()->get()->map(fn (ManuscriptChapter $chapter): array => [
            'ref' => 'chapter:'.$chapter->id, 'title' => $chapter->title, 'position' => $chapter->position,
            'word_count' => $chapter->word_count, 'revision' => $chapter->revision, 'draft' => 'working',
        ])->all();
        $finished = $manuscript->drafts()->withCount('chapters')->get()->map(fn ($draft): array => [
            'number' => $draft->number, 'name' => $draft->name, 'note' => $draft->note, 'chapter_count' => $draft->chapters_count,
            'finished_at' => $draft->created_at->toIso8601String(), 'prose_available' => false,
        ])->all();

        return $this->page($manuscript->title, $this->json([
            'manuscript' => ['id' => $manuscript->id, 'title' => $manuscript->title, 'description' => $manuscript->description],
            'working_draft_number' => $manuscript->working_draft_number, 'chapters' => $chapters, 'finished_drafts' => $finished,
        ]), route('studio.manuscript', $manuscript), ['type' => 'manuscript', 'revision' => $manuscript->revision, 'state' => 'working draft '.$manuscript->working_draft_number]);
    }

    private function chapter(User $user, int $id): array
    {
        $chapter = ManuscriptChapter::find($id);
        if (! $chapter) {
            throw new ExternalMaterialUnavailable;
        }
        [$manuscript] = $this->resolve($user, 'manuscript', Manuscript::class, $chapter->manuscript_id);

        return $this->page($chapter->title, $chapter->content_md ?? '', route('studio.manuscript', ['manuscript' => $manuscript, 'chapter' => $chapter->id]),
            ['type' => 'chapter', 'revision' => $chapter->revision, 'state' => 'working draft '.$manuscript->working_draft_number, 'manuscript' => 'manuscript:'.$manuscript->id, 'word_count' => $chapter->word_count]);
    }

    private function workspaceExercise(User $user, int $id): array
    {
        $exercise = WorldExercise::find($id);
        if (! $exercise) {
            throw new ExternalMaterialUnavailable;
        }
        [$world, $grant] = $this->resolve($user, 'world', World::class, $exercise->world_id);
        if (! $grant->includes_workspace || ! Gate::forUser($user)->allows('view', $exercise)) {
            throw new ExternalMaterialUnavailable;
        }

        return [$exercise, $world];
    }

    private function exercise(User $user, int $id): array
    {
        [$exercise, $world] = $this->workspaceExercise($user, $id);
        $ideas = $exercise->ideas()->withTrashed()->reorder('id')->get()->map(fn (SandboxIdea $idea): array => [
            'id' => $idea->id, 'kind' => $idea->kind, 'title' => $idea->title, 'notes_md' => $idea->notes_md, 'uncertainty' => $idea->uncertainty,
            'alternative' => $idea->alternative, 'lane' => $idea->lane, 'date_label' => $idea->date_label, 'fields' => $idea->fields,
            'target_key' => $idea->target_key, 'target_revision' => $idea->target_revision, 'revision' => $idea->revision, 'origin' => $idea->origin,
            'state' => match (true) {
                $idea->trashed() => 'archived',
                $idea->canonised_at !== null => 'accepted',
                default => 'pending',
            },
        ])->all();
        $links = $exercise->links()->get()->map(fn ($link): array => $link->only(['source_id', 'target_id', 'source_key', 'target_key', 'kind', 'label', 'notes_md']))->all();
        $changes = $exercise->changes()->whereNull('reversed_at')->limit(10)->get()->map(fn ($change): array => ['accepted_at' => $change->created_at->toIso8601String(), 'changes' => $change->changes])->all();

        return $this->page($exercise->title, $this->json([
            'exercise' => ['id' => $exercise->id, 'title' => $exercise->title, 'purpose' => $exercise->purpose, 'instructions' => $exercise->instructions, 'author_summary' => $exercise->summary, 'world' => 'world:'.$world->id],
            'ideas' => $ideas, 'links' => $links, 'last_accepted_changes' => $changes, 'discussion' => 'exercise:'.$exercise->id.'/messages',
        ]), route('studio.world.sandbox', ['world' => $world, 'exercise' => $exercise->id]), ['type' => 'exercise', 'revision' => $exercise->revision, 'state' => 'current']);
    }

    private function exerciseMessages(User $user, int $id): array
    {
        [$exercise, $world] = $this->workspaceExercise($user, $id);
        $transcript = $exercise->messages()->with('import')->get()->map(fn (AssistantMessage $message): string => '['.$message->id.' '.$message->role.($message->isImported() ? ' imported: '.$message->import->title.' '.json_encode($message->import->external_origin, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '').']'."\n".$message->content)->implode("\n\n");

        return $this->page($exercise->title.' discussion', $transcript, route('studio.world.sandbox', ['world' => $world, 'exercise' => $exercise->id]), ['type' => 'exercise_messages', 'revision' => $exercise->revision, 'state' => 'current']);
    }

    private function collection(User $user, int $id): array
    {
        [$collection, $grant] = $this->resolve($user, 'collection', PoetryCollection::class, $id);
        $poems = $collection->poems()->orderBy('title')->orderBy('id')->get(['id', 'title', 'is_published'])->map(fn ($poem): array => ['ref' => 'poem:'.$poem->id, 'title' => $poem->title, 'published' => $poem->is_published])->all();

        return $this->page($collection->title, $this->json(['collection' => ['id' => $collection->id, 'title' => $collection->title, 'description' => $collection->description, 'published' => $collection->is_published], 'workspace_included' => $grant->includes_workspace, 'poems' => $poems]),
            url('/dashboard/collections/'.$collection->id.'/edit'), ['type' => 'collection', 'state' => $collection->is_published ? 'published' : 'unpublished']);
    }

    private function poem(User $user, int $id): array
    {
        [$poem, $workspace] = $this->accessiblePoem($user, $id);
        // Retain the exact version read, so an analysis imported later can attach to it.
        $snapshot = PoemAnalysisSnapshot::capture($poem);
        $metadata = [
            'type' => 'poem', 'fingerprint' => $snapshot->fingerprint, 'snapshot_id' => $snapshot->id, 'state' => $poem->is_published ? 'published' : 'unpublished',
            'line_count' => $poem->content_md === '' || $poem->content_md === null ? 0 : count(preg_split('/\R/u', $poem->content_md)),
            'collection' => $poem->collection ? ['ref' => 'collection:'.$poem->collection->id, 'title' => $poem->collection->title] : null,
            'workspace_included' => $workspace,
        ];
        if ($workspace) {
            $metadata['author_notes'] = $poem->authorNotes()->get()->map(fn (PoemAuthorNote $note): array => ['id' => $note->id, 'content' => $note->content])->all();
            $metadata['analyses'] = $poem->analysisConversations()->reorder('id')->get()->map(fn (PoemAnalysisConversation $analysis): array => ['ref' => 'analysis:'.$analysis->id, 'title' => $analysis->title])->all();
        }

        return $this->page($poem->title, $poem->content_md ?? '', url('/dashboard/poems/'.$poem->id.'/edit'), $metadata);
    }

    private function analysis(User $user, int $id): array
    {
        $conversation = PoemAnalysisConversation::with('poem')->find($id);
        if (! $conversation) {
            throw new ExternalMaterialUnavailable;
        }
        [, $workspace] = $this->accessiblePoem($user, $conversation->poem_id);
        if (! $workspace || ! Gate::forUser($user)->allows('update', $conversation)) {
            throw new ExternalMaterialUnavailable;
        }
        $turns = $conversation->turns()->get();
        $messages = $conversation->messages()->with('import')->get();
        $turnSnapshot = fn (AssistantMessage $message) => $turns->last(fn ($turn) => $turn->message_id === $message->id || $turn->response_id === $message->id)?->request_context['snapshot_id'] ?? null;
        // Imported answers have no turn; the import itself records the version they analysed.
        $snapshotIds = $messages->map(fn (AssistantMessage $message) => $message->isImported() ? $message->import->snapshot_id : $turnSnapshot($message))->filter()->unique();
        $fingerprints = PoemAnalysisSnapshot::whereIn('id', $snapshotIds)->pluck('fingerprint', 'id');
        $messages = $messages->map(function (AssistantMessage $message) use ($turnSnapshot, $fingerprints): array {
            $snapshotId = $message->isImported() ? $message->import->snapshot_id : $turnSnapshot($message);

            return ['id' => $message->id, 'role' => $message->role, 'content' => $message->content, 'snapshot_id' => $snapshotId, 'snapshot_fingerprint' => $fingerprints[$snapshotId] ?? null] + $this->importDetails($message);
        })->all();

        return $this->page($conversation->title ?: 'Poem analysis', $this->json(['analysis' => ['id' => $conversation->id, 'poem' => 'poem:'.$conversation->poem_id], 'messages' => $messages]),
            url('/dashboard/poems/'.$conversation->poem_id.'/analysis'), ['type' => 'analysis', 'state' => 'saved']);
    }

    private function searchChapters(User $user, string $like, string $query): array
    {
        $manuscripts = $this->granted($user, 'manuscript', Manuscript::class, 'view')->pluck('id')->all();

        return ManuscriptChapter::whereIn('manuscript_id', $manuscripts ?: [0])->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('content_md', 'like', $like))
            ->orderBy('manuscript_id')->orderBy('position')->orderBy('id')->limit(self::MAX_RESULTS)->get()
            ->map(fn ($chapter): array => $this->hit('chapter:'.$chapter->id, $chapter->title, route('studio.manuscript', ['manuscript' => $chapter->manuscript_id, 'chapter' => $chapter->id]), $chapter->content_md ?? '', $query))->all();
    }

    private function searchWorldRecords(User $user, array $grants, string $like, string $query): array
    {
        $hits = [];
        foreach ($this->granted($user, 'world', World::class, 'view') as $world) {
            foreach (ProposalTypes::definitions() as $kind => $definition) {
                if (! isset($definition['relation'])) {
                    continue;
                }
                $related = $world->{$definition['relation']}();
                $table = $related->getRelated()->getTable();
                $columns = [$definition['title'], ...array_intersect(self::SEARCHABLE_WORLD_COLUMNS, Schema::getColumnListing($table))];
                $matches = $related->withTrashed()->where(fn ($q) => collect($columns)->each(fn ($column) => $q->orWhere($table.'.'.$column, 'like', $like)))
                    ->reorder($table.'.id')->limit(self::MAX_RESULTS)->get();
                foreach ($matches as $record) {
                    $key = $kind.':'.$record->id;
                    $body = collect($columns)->map(fn ($column) => (string) $record->{$column})->implode("\n");
                    $hits[] = $this->hit('world:'.$world->id.'/'.$key, (string) $record->{$definition['title']}, $this->worlds->url($world, $key), $body, $query);
                }
            }
        }

        return $hits;
    }

    private function searchPoems(User $user, array $grants, string $like, string $query): array
    {
        return $this->readablePoems($user, $grants)->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('content_md', 'like', $like))->limit(self::MAX_RESULTS)->get()
            ->map(fn ($poem): array => $this->hit('poem:'.$poem->id, $poem->title, url('/dashboard/poems/'.$poem->id.'/edit'), $poem->content_md ?? '', $query))->all();
    }

    private function searchWorkspace(User $user, array $grants, string $like, string $query): array
    {
        $hits = [];
        $worlds = $this->granted($user, 'world', World::class, 'view')->filter(fn ($world) => $grants['world'][$world->id]->includes_workspace);
        $exercises = WorldExercise::whereIn('world_id', $worlds->pluck('id')->all() ?: [0])->get()->filter(fn ($exercise) => Gate::forUser($user)->allows('view', $exercise))->keyBy('id');
        foreach (SandboxIdea::whereIn('exercise_id', $exercises->keys()->all() ?: [0])->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('notes_md', 'like', $like))->orderBy('exercise_id')->orderBy('id')->limit(self::MAX_RESULTS)->get() as $idea) {
            $exercise = $exercises[$idea->exercise_id];
            $hits[] = $this->hit('exercise:'.$exercise->id, $exercise->title.' · '.$idea->title, route('studio.world.sandbox', ['world' => $exercise->world_id, 'exercise' => $exercise->id]), $idea->title."\n".$idea->notes_md, $query);
        }
        foreach (AssistantMessage::whereIn('exercise_id', $exercises->keys()->all() ?: [0])->where('content', 'like', $like)->orderBy('exercise_id')->orderBy('id')->limit(self::MAX_RESULTS)->get() as $message) {
            $exercise = $exercises[$message->exercise_id];
            $hits[] = $this->hit('exercise:'.$exercise->id.'/messages', $exercise->title.' discussion', route('studio.world.sandbox', ['world' => $exercise->world_id, 'exercise' => $exercise->id]), $message->content, $query);
        }
        $conversations = PoemAnalysisConversation::whereIn('poem_id', $this->readablePoems($user, $grants)->pluck('id')->all() ?: [0])->where('author_id', $user->id)->get()
            ->filter(fn ($conversation) => Gate::forUser($user)->allows('update', $conversation) && $this->poemWorkspaceIncluded($conversation->poem, $grants))->keyBy('id');
        foreach (AssistantMessage::whereIn('poem_conversation_id', $conversations->keys()->all() ?: [0])->where('content', 'like', $like)->orderBy('poem_conversation_id')->orderBy('id')->limit(self::MAX_RESULTS)->get() as $message) {
            $conversation = $conversations[$message->poem_conversation_id];
            $hits[] = $this->hit('analysis:'.$conversation->id, $conversation->title ?: 'Poem analysis', url('/dashboard/poems/'.$conversation->poem_id.'/analysis'), $message->content, $query);
        }

        return $hits;
    }

    private function hit(string $ref, string $title, string $url, string $body, string $query): array
    {
        $position = mb_stripos($body, $query);
        $excerpt = $position === false ? mb_substr($body, 0, 160) : (($position > 80 ? '…' : '').mb_substr($body, max(0, $position - 80), 80 + mb_strlen($query) + 80).'…');

        return ['id' => $ref, 'title' => $title, 'url' => $url, 'text' => trim(preg_replace('/\s+/u', ' ', $excerpt))];
    }

    private function page(string $title, string $text, string $url, array $metadata): array
    {
        return ['title' => $title, 'text' => $text, 'url' => $url, 'metadata' => $metadata];
    }

    private function paged(string $ref, array $page, int $offset, int $limit): array
    {
        $total = mb_strlen($page['text']);
        $next = $offset + $limit < $total ? $offset + $limit : null;

        return [
            'id' => $ref, 'title' => $page['title'], 'text' => mb_substr($page['text'], $offset, $limit), 'url' => $page['url'],
            'metadata' => $page['metadata'] + ['offset' => $offset, 'total_characters' => $total, 'next_offset' => $next, 'complete' => $next === null],
        ];
    }

    private function importDetails(AssistantMessage $message): array
    {
        return ['imported' => $message->isImported()] + ($message->isImported() ? ['import_title' => $message->import->title, 'external_origin' => $message->import->external_origin] : []);
    }

    private function json(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
