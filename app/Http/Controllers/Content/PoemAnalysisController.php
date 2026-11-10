<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\PoemAnalysisRequest;
use App\Models\AssistantConnection;
use App\Models\AssistantMessage;
use App\Models\ExchangeImport;
use App\Models\Poem;
use App\Models\PoemAnalysisConversation;
use App\Models\PoemAnalysisSnapshot;
use App\Services\Exchange\ExchangeImports;
use App\Services\Poetry\PoemAnalysis;
use App\Services\Worldbuilding\AssistantModels;
use App\Services\Worldbuilding\AssistantProviders;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

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
        $messages = $record->messages()->get(['id', 'role', 'content', 'import_id']);
        $imports = ExchangeImport::query()->saved()->whereIn('id', $messages->pluck('import_id')->filter())->orderBy('id')->get();
        $snapshotIds = $turns->toBase()->map(fn ($turn) => $turn->request_context['snapshot_id'])->merge($imports->pluck('snapshot_id'))->filter()->unique();

        return response()->json([
            'conversation' => $record->only(['id', 'title', 'connection_id']),
            'messages' => $messages,
            'imports' => $imports->map(fn (ExchangeImport $import): array => [
                'id' => $import->id,
                'message_id' => $messages->firstWhere('import_id', $import->id)?->id,
                'title' => $import->title,
                'snapshot_id' => $import->snapshot_id,
                'external_origin' => $import->external_origin,
                'saved_at' => $import->saved_at?->toIso8601String(),
                'suggested_notes' => $import->payload['poetry']['suggested_notes'] ?? [],
            ])->values(),
            'turns' => $turns->map(fn ($turn) => $turn->only(['id', 'connection_id', 'message_id', 'response_id', 'status', 'provider', 'model', 'error', 'progress', 'request_context', 'created_at'])),
            'snapshots' => PoemAnalysisSnapshot::where('poem_id', $poem)->whereIn('id', $snapshotIds)->get(),
        ]);
    }

    private function import(int $poem, int $import): ExchangeImport
    {
        $this->poem($poem);

        return ExchangeImport::where('author_id', auth()->id())
            ->where('destination_type', 'poem_analysis')
            ->where('poem_id', $poem)
            ->findOrFail($import);
    }

    private function importSummary(ExchangeImport $import): array
    {
        return [
            'id' => $import->id,
            'title' => $import->title,
            'saved' => $import->isSaved(),
            'saved_at' => $import->saved_at?->toIso8601String(),
            'preview' => $import->receipt['preview'] ?? null,
            'suggested_notes' => $import->payload['poetry']['suggested_notes'] ?? [],
            'external_origin' => $import->external_origin,
            'conversation_id' => $import->isSaved() ? $import->poem_conversation_id : null,
        ];
    }

    public function previewImport(PoemAnalysisRequest $request, int $poem, ExchangeImports $imports)
    {
        $record = $this->poem($poem);
        try {
            $import = $imports->preview($request->user(), $request->validated('markdown'));
        } catch (ModelNotFoundException) {
            throw ValidationException::withMessages(['destination.poem' => 'This package names a poem or conversation that is not available to you.']);
        }
        if ($import->destination_type !== 'poem_analysis' || $import->poem_id !== $record->id) {
            // A saved import is a record of earlier work; only an unsaved preview is ours to throw away.
            if (! $import->isSaved()) {
                $import->discard();
            }
            throw ValidationException::withMessages(['destination.poem' => 'This package is not an analysis of "'.$record->title.'". Import it from the poem it names.']);
        }

        return response()->json($this->importSummary($import));
    }

    public function saveImport(int $poem, int $import, ExchangeImports $imports)
    {
        $saved = $imports->save(auth()->user(), $this->import($poem, $import));

        return response()->json([
            'conversation_id' => $saved->receipt['conversation_id'],
            'message_id' => $saved->receipt['message_id'],
            'snapshot_id' => $saved->receipt['snapshot_id'],
            'newer_version' => $saved->receipt['newer_version'],
            'warnings' => $saved->receipt['warnings'],
        ]);
    }

    public function discardImport(int $poem, int $import)
    {
        $record = $this->import($poem, $import);
        abort_if($record->isSaved(), 409, 'A saved import cannot be discarded.');
        $record->discard();

        return response()->noContent();
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
