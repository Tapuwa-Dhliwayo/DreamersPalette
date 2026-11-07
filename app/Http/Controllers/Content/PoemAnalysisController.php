<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\PoemAnalysisRequest;
use App\Models\AssistantConnection;
use App\Models\AssistantMessage;
use App\Models\Poem;
use App\Models\PoemAnalysisConversation;
use App\Models\PoemAnalysisSnapshot;
use App\Services\Poetry\PoemAnalysis;
use App\Services\Worldbuilding\AssistantModels;
use App\Services\Worldbuilding\AssistantProviders;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PoemAnalysisController extends Controller
{
    private function poem(int $poem): Poem
    {
        $record = Poem::with('collection')->where('author_id', auth()->id())->findOrFail($poem);
        Gate::authorize('update', $record);

        return $record;
    }

    private function conversation(int $poem, int $conversation): PoemAnalysisConversation
    {
        $record = $this->poem($poem)->analysisConversations()->findOrFail($conversation);
        Gate::authorize('update', $record);

        return $record;
    }

    public function index(int $poem)
    {
        $record = $this->poem($poem);

        return response()->json([
            'poem' => $record->only(['id', 'title', 'content_md', 'collection_id']),
            'collection' => $record->collection?->only(['id', 'title', 'description']),
            'fingerprint' => PoemAnalysisSnapshot::fingerprint($record),
            'conversations' => $record->analysisConversations()->get(['id', 'title', 'connection_id', 'updated_at']),
            'archived_conversations' => $record->analysisConversations()->onlyTrashed()->get(['id', 'title']),
            'notes' => $record->authorNotes()->get(['id', 'content', 'source_message_id']),
            'retired_notes' => $record->authorNotes()->onlyTrashed()->get(['id', 'content']),
            'comparison_poems' => Poem::where('author_id', auth()->id())->whereKeyNot($poem)->orderBy('title')->get(['id', 'title']),
            'connections' => AssistantConnection::where('user_id', auth()->id())->where('enabled', true)
                ->whereIn('provider', array_keys(app(AssistantProviders::class)->availableProviders(auth()->id())))
                ->get(['id', 'name', 'provider', 'model']),
        ]);
    }

    public function create(PoemAnalysisRequest $request, int $poem)
    {
        $record = $this->poem($poem);
        $conversation = $record->analysisConversations()->make($request->validated());
        $conversation->author_id = auth()->id();
        $conversation->save();

        return response()->json($conversation->only(['id', 'title', 'connection_id']), 201);
    }

    public function show(int $poem, int $conversation, PoemAnalysis $analysis)
    {
        $record = $this->conversation($poem, $conversation);
        $analysis->expire($record);
        $turns = $record->turns()->get();
        $snapshotIds = $turns->map(fn ($turn) => $turn->request_context['snapshot_id'])->unique();

        return response()->json([
            'conversation' => $record->only(['id', 'title', 'connection_id']),
            'messages' => $record->messages()->get(['id', 'role', 'content']),
            'turns' => $turns->map(fn ($turn) => $turn->only(['id', 'connection_id', 'message_id', 'response_id', 'status', 'provider', 'model', 'error', 'progress', 'request_context', 'created_at'])),
            'snapshots' => PoemAnalysisSnapshot::where('poem_id', $poem)->whereIn('id', $snapshotIds)->get(),
        ]);
    }

    public function models(int $connection)
    {
        $record = AssistantConnection::where('user_id', auth()->id())->findOrFail($connection);
        Gate::authorize('update', $record);

        return response()->json(app(AssistantModels::class)->catalogue($record, request()->boolean('refresh')));
    }

    public function send(PoemAnalysisRequest $request, int $poem, int $conversation, PoemAnalysis $analysis)
    {
        $record = $this->conversation($poem, $conversation);
        $connection = AssistantConnection::where('user_id', auth()->id())->findOrFail($request->validated('connection_id'));
        $turn = $analysis->send($record, $connection, $request->validated());

        return response()->json(['turn_id' => $turn->id], 202);
    }

    public function stop(int $poem, int $conversation, int $turn, PoemAnalysis $analysis)
    {
        $analysis->stop($this->conversation($poem, $conversation)->turns()->findOrFail($turn));

        return response()->noContent();
    }

    public function retry(PoemAnalysisRequest $request, int $poem, int $conversation, int $turn, PoemAnalysis $analysis)
    {
        $fields = $request->validated();
        $attempt = $analysis->retry($this->conversation($poem, $conversation)->turns()->findOrFail($turn), $fields['request_uuid']);

        return response()->json(['turn_id' => $attempt->id], 202);
    }

    public function archive(int $poem, int $conversation)
    {
        DB::transaction(function () use ($poem, $conversation): void {
            $record = $this->conversation($poem, $conversation)->newQuery()->whereKey($conversation)->lockForUpdate()->firstOrFail();
            $record->archive();
        });

        return response()->noContent();
    }

    public function restore(int $poem, int $conversation)
    {
        $record = $this->poem($poem)->analysisConversations()->onlyTrashed()->where('author_id', auth()->id())->findOrFail($conversation);
        $record->restore();

        return response()->noContent();
    }

    public function note(PoemAnalysisRequest $request, int $poem)
    {
        $record = $this->poem($poem);
        $fields = $request->validated();
        if ($fields['source_message_id'] ?? null) {
            $message = AssistantMessage::whereIn('poem_conversation_id', $record->analysisConversations()->select('id'))
                ->where('role', 'user')->findOrFail($fields['source_message_id']);
            abort_unless($message->content === $fields['content'], 422, 'Save the author message as written, or add a separate note.');
        }
        $note = isset($fields['note_id']) ? $record->authorNotes()->findOrFail($fields['note_id']) : $record->authorNotes()->make();
        if ($note->exists && $note->content !== $fields['content'] && ! isset($fields['source_message_id'])) {
            $note->source_message_id = null;
        }
        $note->fill(collect($fields)->only(['content', 'source_message_id'])->all())->save();

        return response()->json($note->only(['id', 'content', 'source_message_id']));
    }

    public function restoreNote(int $poem, int $note)
    {
        $this->poem($poem)->authorNotes()->onlyTrashed()->findOrFail($note)->restore();

        return response()->noContent();
    }

    public function retireNote(int $poem, int $note)
    {
        $this->poem($poem)->authorNotes()->findOrFail($note)->delete();

        return response()->noContent();
    }
}
