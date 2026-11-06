<?php

namespace App\Services\Worldbuilding;

use App\Models\AssistantConnection;
use App\Models\AssistantMessage;
use App\Models\AssistantTurn;
use App\Models\User;
use App\Models\World;
use App\Models\WorldExercise;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;

class AssistantConversation
{
    public function __construct(
        private ExerciseContext $context,
        private AssistantProviders $providers,
        private SandboxProposals $proposals,
        private SourceReading $reading,
        private AssistantModels $models,
        private ContextRetrieval $retrieval,
    ) {}

    public function send(
        WorldExercise $exercise,
        AssistantConnection $connection,
        string $content,
        string $mode,
        string $requestUuid,
        string $readingDepth = 'focused',
        array $preferences = [],
        array $recovery = [],
    ): AssistantTurn {
        if (! in_array($mode, ['conversation', 'summary'], true)) {
            throw new \InvalidArgumentException('Unknown assistant request.');
        }
        Gate::authorize('update', $exercise);
        Gate::authorize('update', $connection);
        $this->models->assertSelection($connection, $this->requestedModel($connection, $preferences), $preferences['effort'] ?? 'default');
        $turn = DB::transaction(function () use ($exercise, $connection, $content, $mode, $requestUuid, $readingDepth, $preferences, $recovery): AssistantTurn {
            User::whereKey($connection->user_id)->lockForUpdate()->firstOrFail();
            World::lockForUpdate()->findOrFail($exercise->world_id);
            $exercise = WorldExercise::lockForUpdate()->findOrFail($exercise->id);
            Gate::authorize('update', $exercise);
            if ($existing = $exercise->turns()->where('request_uuid', $requestUuid)->first()) {
                return $existing;
            }
            if ($this->hasReachedActiveTurnLimit($connection)) {
                throw ValidationException::withMessages(['conversationForm.content' => 'Finish or stop another active question before starting this one.']);
            }
            if (($preferences['effort'] ?? 'default') !== 'default' && ! $connection->isLocal()) {
                throw ValidationException::withMessages(['conversationForm.effort' => 'Thinking effort is available for local ChatGPT and Claude connections. Use provider default for this API connection.']);
            }
            if (! $connection->enabled) {
                throw ValidationException::withMessages(['conversationForm.content' => 'Reconnect this provider before sending.']);
            }
            if ($exercise->turns()->active()->exists()) {
                throw ValidationException::withMessages(['conversationForm.content' => 'Stop or finish the current response before sending another.']);
            }
            $message = $exercise->messages()->create(['role' => 'user', 'content' => $content]);
            $requestContext = $this->context->capture($exercise);
            if ($mode !== 'summary') {
                $requestContext['response_contract'] = 2;
            }
            if ($recovery) {
                $requestContext['recovery'] = $recovery;
                $requestContext['passages'] = [];
            }
            $requestContext['connection_model'] = $connection->model;
            $requestContext['effort'] = $preferences['effort'] ?? 'default';
            $requestContext['auto_continue'] = $preferences['auto_continue'] ?? true;
            $turn = $exercise->turns()->create([
                'connection_id' => $connection->id,
                'message_id' => $message->id,
                'request_uuid' => $requestUuid,
                'provider' => $connection->provider,
                'model' => $this->requestedModel($connection, $preferences),
                'mode' => $mode,
                'context_manifest' => [],
                'prompt' => '',
                'request_context' => $requestContext,
                'reading_depth' => $readingDepth,
                'stage' => 'preparing',
                'progress' => 'Question saved. Preparing the source collection.',
            ]);
            $exercise->forceFill(['connection_id' => $connection->id])->save();

            return $turn;
        });
        $this->launch($turn->refresh());

        return $turn;
    }

    public function recover(
        WorldExercise $exercise,
        AssistantConnection $connection,
        ?int $messageId = null,
        ?int $ideaId = null,
        array $preferences = [],
    ): AssistantTurn {
        Gate::authorize('update', $exercise);
        Gate::authorize('update', $connection);
        $idea = $ideaId ? $exercise->ideas()->findOrFail($ideaId) : null;
        if ($idea && (! $idea->canEdit() || ! in_array($idea->kind, ['thought', 'question'], true))) {
            throw ValidationException::withMessages(['recovery' => 'Use Discuss to refine this record proposal.']);
        }
        $sourceMessageId = $messageId ?? $idea?->source_message_id;
        $message = $sourceMessageId ? $exercise->messages()->findOrFail($sourceMessageId) : null;
        $sourceTurn = $message ? $exercise->turns()->where('response_id', $message->id)->first() : null;
        if (! $idea && (! $message || $message->role !== 'assistant' || ! $sourceTurn || $sourceTurn->status !== 'completed')) {
            throw ValidationException::withMessages(['recovery' => 'Choose a completed response from this exercise.']);
        }
        $content = $idea?->notes_md ?? $message?->content;
        if (! filled($content)) {
            throw ValidationException::withMessages(['recovery' => 'There is no saved content to prepare.']);
        }
        $coverage = $sourceTurn?->evidenceLimitations() ?? $idea?->provenance['coverage_status'] ?? [];
        if (! $sourceTurn && $idea && $idea->origin !== 'assistant') {
            $coverage = ['recorded' => true, 'partial' => false, 'scope' => 'author_thought'];
        }
        $recovery = [
            'message_id' => $message?->id,
            'idea_id' => $idea?->id,
            'idea_revision' => $idea?->revision,
            'source_turn_id' => $sourceTurn?->id,
            'coverage_status' => $coverage,
            'original_request' => $sourceTurn
                ? $sourceTurn->question()
                : ($message?->content ?? 'Prepare linked updates from this thought where its intended subject and changes are clear; otherwise ask a focused question.'),
            'content' => $sourceTurn ? $this->reading->referenceTokens($sourceTurn, $content) : $content,
            'citation_turn_ids' => $sourceTurn
                ? array_unique([$sourceTurn->id, ...($sourceTurn->request_context['recovery']['citation_turn_ids'] ?? [])])
                : [],
        ];
        // The same request always yields the same UUID, so send() returns the earlier turn instead of preparing a duplicate.
        $uuid = (string) Uuid::uuid5(Uuid::NAMESPACE_URL, implode(':', [
            'dreamers',
            'recover',
            $exercise->id,
            $message?->id ?? 0,
            $idea?->id ?? 0,
            $idea?->revision ?? 0,
            hash('sha256', $content),
            $connection->id,
            $this->requestedModel($connection, $preferences),
            $preferences['effort'] ?? 'default',
        ]));

        return $this->send(
            $exercise,
            $connection,
            'Prepare linked record updates from this saved response. Follow the original request, keep supporting citations and uncertainties, and show me the drafts for review without rereading the manuscript.',
            'conversation',
            $uuid,
            'focused',
            $preferences,
            $recovery,
        );
    }

    private function requestedModel(AssistantConnection $connection, array $preferences): string
    {
        return ($preferences['model'] ?? '') ?: $connection->model;
    }

    private function hasReachedActiveTurnLimit(AssistantConnection $connection): bool
    {
        return AssistantTurn::forAuthor($connection->user_id)->active()->count() >= config('worldbuilding.concurrent_turns_per_user');
    }

    private function launch(AssistantTurn $turn): void
    {
        if ($turn->status === 'queued') {
            $command = 'setsid nohup '.escapeshellarg(PHP_BINARY).' '.escapeshellarg(base_path('artisan')).' studio:assistant-turn '.(int) $turn->id.' >> '.escapeshellarg(storage_path('logs/assistant-worker.log')).' 2>&1 < /dev/null &';
            exec($command);
        }
    }

    public function retry(AssistantTurn $turn, bool $autoContinue = true): void
    {
        Gate::authorize('update', $turn->exercise);
        $connection = AssistantConnection::where('user_id', auth()->id())->findOrFail($turn->connection_id);
        Gate::authorize('update', $connection);
        if (! $connection->enabled || $connection->provider !== $turn->provider || $connection->model !== ($turn->request_context['connection_model'] ?? $turn->model)) {
            throw ValidationException::withMessages(['conversationForm.content' => 'Restore the original connection and model to retry this saved question, or send a new request with a different connection.']);
        }
        DB::transaction(function () use ($turn, $autoContinue, $connection): void {
            User::whereKey($connection->user_id)->lockForUpdate()->firstOrFail();
            World::lockForUpdate()->findOrFail($turn->exercise->world_id);
            $exercise = WorldExercise::lockForUpdate()->findOrFail($turn->exercise_id);
            Gate::authorize('update', $exercise);
            $turn = $exercise->turns()->lockForUpdate()->findOrFail($turn->id);
            if (! in_array($turn->status, ['failed', 'paused', 'cancelled'], true)) {
                return;
            }
            if ($exercise->turns()->active()->exists()) {
                throw ValidationException::withMessages(['conversationForm.content' => 'Finish or stop the current response before retrying.']);
            }
            if ($this->hasReachedActiveTurnLimit($connection)) {
                throw ValidationException::withMessages(['conversationForm.content' => 'Finish or stop another active question before resuming.']);
            }
            $settings = $turn->request_context ?? $this->context->capture($exercise);
            // A summary after the pending question must not consume that question's history.
            if (
                $exercise->summary_through_id
                && $exercise->summary_through_id < $turn->message_id
                && $exercise->summary_through_id > ($settings['summary_through_id'] ?? 0)
            ) {
                $settings['summary'] = $exercise->summary;
                $settings['summary_through_id'] = $exercise->summary_through_id;
                unset($settings['derived_memory'], $settings['memory_parts']);
            }
            $settings['auto_continue'] = $autoContinue;
            $turn->update(['request_context' => $settings, 'status' => 'queued', 'run_uuid' => null, 'stage' => 'preparing', 'progress' => 'Resuming the saved question and completed reading.', 'error' => null, 'finished_at' => null, 'acknowledged_at' => null]);
        });
        $this->launch($turn->refresh());
    }

    public function run(AssistantTurn $turn): void
    {
        $runId = (string) Str::uuid();
        $claimed = AssistantTurn::whereKey($turn->id)->where('status', 'queued')->update(['status' => 'running', 'run_uuid' => $runId, 'started_at' => now()]);
        if (! $claimed) {
            return;
        }
        $turn->refresh();
        $exercise = $turn->exercise;
        $response = $exercise->messages()->create(['role' => 'assistant', 'content' => '']);
        $turn->update(['response_id' => $response->id]);
        try {
            $author = $exercise->world?->author_id;
            $user = User::findOrFail($author);
            Gate::forUser($user)->authorize('update', $exercise);
            $connection = AssistantConnection::where('user_id', $author)->whereKey($turn->connection_id)->firstOrFail();
            if ($connection->provider !== $turn->provider || $connection->model !== ($turn->request_context['connection_model'] ?? $turn->model)) {
                throw new \RuntimeException('The connection changed before the turn started. Send a new request.');
            }
            $connection->model = $turn->model;
            $connection->setAttribute('effort', $turn->request_context['effort'] ?? 'default');
            $cancelled = fn () => $turn->fresh()->status !== 'running' || $turn->fresh()->run_uuid !== $runId || ! $connection->fresh()->enabled || ! $exercise->fresh() || ! Gate::forUser($user)->allows('update', $exercise->fresh());
            if ($turn->request_context !== null) {
                $turn->update(['stage' => 'preparing', 'progress' => 'Preparing the saved question and source collection.']);
                $brief = $this->context->readingBrief($exercise, $turn);
                $settings = $turn->request_context;
                $settings['focus_names'] = $brief['focus_names'];
                $turn->update(['request_context' => $settings]);
                $evidence = $this->reading->prepare($turn, $connection, $brief['prompt'], $cancelled);
                if ($evidence['paused'] ?? false) {
                    AssistantTurn::whereKey($turn->id)->where('status', 'running')->where('run_uuid', $runId)->update(['status' => 'paused', 'progress' => $evidence['progress'], 'error' => $evidence['progress'], 'finished_at' => now()]);

                    return;
                }
                if ($turn->mode !== 'summary') {
                    $this->retrieval->prepare($turn, $connection, $cancelled);
                    $turn->refresh();
                    if (empty($turn->request_context['recovery'])) {
                        $evidence = $this->reading->evidence($turn);
                    }
                }
                $context = $this->context->build($exercise, $turn->mode, $turn, $evidence);
                if ($cancelled()) {
                    return;
                }
                $turn->update(['prompt' => $context['prompt'], 'context_manifest' => $context['manifest'], 'stage' => 'responding', 'progress' => 'Writing a response with the recorded source coverage.']);
            }
            $structured = $turn->usesStructuredReply();
            $displayText = '';
            $text = '';
            $lastWrite = 0;
            $this->providers->stream($connection, $turn->prompt, function (string $delta) use (&$text, &$lastWrite, $response, $structured): void {
                $text .= $delta;
                if (! $structured && microtime(true) - $lastWrite > 0.25) {
                    $response->update(['content' => $text]);
                    $lastWrite = microtime(true);
                }
            }, $cancelled, $turn);
            if (! $structured) {
                $response->update(['content' => $text]);
            }
            if ($turn->fresh()->status !== 'running' || $turn->fresh()->run_uuid !== $runId) {
                return;
            }
            $proposal = null;
            if ($structured) {
                $proposal = $this->proposals->decode($text);
                $displayText = $this->reading->citations($turn, $proposal['reply']);
                $response->update(['content' => $displayText]);
                $proposal = $this->proposals->prepare($turn, $proposal);
                foreach ($proposal['ideas'] as &$idea) {
                    if (isset($idea['notes_md'])) {
                        $idea['notes_md'] = $this->reading->citations($turn, $idea['notes_md']);
                    }
                }
                unset($idea);
            }
            if ($proposal === null) {
                $response->update(['content' => $this->reading->citations($turn, $text)]);
            }
            // Stage the whole batch atomically; canon still requires the author's explicit review.
            DB::transaction(function () use ($turn, $runId, $proposal, $user): void {
                World::lockForUpdate()->findOrFail($turn->exercise->world_id);
                $current = AssistantTurn::whereKey($turn->id)->lockForUpdate()->firstOrFail();
                if ($current->status !== 'running' || $current->run_uuid !== $runId) {
                    return;
                }
                $current->update(['status' => 'completed', 'finished_at' => now(), 'proposals' => $proposal]);
                if (! empty($proposal['ideas'])) {
                    $this->proposals->collect($current, $user);
                }
            });
        } catch (\Throwable $exception) {
            if (isset($text)) {
                $this->retainReceivedResponse($turn, $response, $runId, $text, $structured ?? false, $displayText ?? '');
            }
            $message = $exception instanceof ValidationException ? collect($exception->errors())->flatten()->first() : (get_class($exception) === \RuntimeException::class ? $exception->getMessage() : 'The response could not finish. Check the connection and retry explicitly.');
            AssistantTurn::whereKey($turn->id)->where('status', 'running')->where('run_uuid', $runId)->update(['status' => 'failed', 'error' => mb_substr($message, 0, 1000), 'finished_at' => now()]);
        }
    }

    // Whatever the provider already sent is kept: plain replies as written, structured ones as raw material the author can ask to re-prepare.
    private function retainReceivedResponse(
        AssistantTurn $turn,
        AssistantMessage $response,
        string $runId,
        string $text,
        bool $structured,
        string $displayText,
    ): void {
        if (! $structured) {
            $response->update(['content' => $text]);

            return;
        }
        $settings = $turn->fresh()->request_context ?? [];
        $settings['received_response'] = mb_substr($text, 0, 100000);
        AssistantTurn::whereKey($turn->id)->where('run_uuid', $runId)->update(['request_context' => json_encode($settings, JSON_THROW_ON_ERROR)]);
        $response->update([
            'content' => $displayText ?: 'The response could not be prepared for review. Your request is saved; no changes were applied.',
        ]);
    }
}
