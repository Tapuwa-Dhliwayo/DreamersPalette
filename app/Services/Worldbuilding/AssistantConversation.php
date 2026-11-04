<?php

namespace App\Services\Worldbuilding;

use App\Models\AssistantConnection;
use App\Models\AssistantTurn;
use App\Models\User;
use App\Models\World;
use App\Models\WorldExercise;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssistantConversation
{
    public function __construct(private ExerciseContext $context, private AssistantProviders $providers, private SandboxProposals $proposals, private SourceReading $reading) {}

    public function send(WorldExercise $exercise, AssistantConnection $connection, string $content, string $mode, string $requestUuid, string $readingDepth = 'focused', array $preferences = []): AssistantTurn
    {
        Gate::authorize('update', $exercise);
        Gate::authorize('update', $connection);
        $turn = DB::transaction(function () use ($exercise, $connection, $content, $mode, $requestUuid, $readingDepth, $preferences): AssistantTurn {
            World::lockForUpdate()->findOrFail($exercise->world_id);
            $exercise = WorldExercise::lockForUpdate()->findOrFail($exercise->id);
            Gate::authorize('update', $exercise);
            if ($existing = $exercise->turns()->where('request_uuid', $requestUuid)->first()) {
                return $existing;
            }
            if (($preferences['effort'] ?? 'default') !== 'default' && ! $connection->isLocal()) {
                throw ValidationException::withMessages(['conversationForm.effort' => 'Thinking effort is available for local ChatGPT and Claude connections. Use provider default for this API connection.']);
            }
            if (! $connection->enabled) {
                throw ValidationException::withMessages(['conversationForm.content' => 'Reconnect this provider before sending.']);
            }
            if ($exercise->turns()->whereIn('status', ['queued', 'running'])->exists()) {
                throw ValidationException::withMessages(['conversationForm.content' => 'Stop or finish the current response before sending another.']);
            }
            $message = $exercise->messages()->create(['role' => 'user', 'content' => $content]);
            $requestContext = $this->context->capture($exercise);
            $requestContext['connection_model'] = $connection->model;
            $requestContext['effort'] = $preferences['effort'] ?? 'default';
            $requestContext['auto_continue'] = $preferences['auto_continue'] ?? true;
            $turn = $exercise->turns()->create(['connection_id' => $connection->id, 'message_id' => $message->id, 'request_uuid' => $requestUuid, 'provider' => $connection->provider, 'model' => ($preferences['model'] ?? '') ?: $connection->model, 'mode' => $mode, 'context_manifest' => [], 'prompt' => '', 'request_context' => $requestContext, 'reading_depth' => $readingDepth, 'stage' => 'preparing', 'progress' => 'Question saved. Preparing the source collection.']);
            $exercise->forceFill(['connection_id' => $connection->id])->save();

            return $turn;
        });
        $this->launch($turn->refresh());

        return $turn;
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
        DB::transaction(function () use ($turn, $autoContinue): void {
            World::lockForUpdate()->findOrFail($turn->exercise->world_id);
            $exercise = WorldExercise::lockForUpdate()->findOrFail($turn->exercise_id);
            Gate::authorize('update', $exercise);
            $turn = $exercise->turns()->lockForUpdate()->findOrFail($turn->id);
            if (! in_array($turn->status, ['failed', 'paused', 'cancelled'], true)) {
                return;
            }
            if ($exercise->turns()->whereIn('status', ['queued', 'running'])->exists()) {
                throw ValidationException::withMessages(['conversationForm.content' => 'Finish or stop the current response before retrying.']);
            }
            $settings = $turn->request_context ?? [];
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
                $brief = $this->context->build($exercise, $turn->mode, $turn);
                $settings = $turn->request_context;
                $settings['focus_names'] = $brief['focus_names'];
                $turn->update(['request_context' => $settings]);
                $evidence = $this->reading->prepare($turn, $connection, $brief['prompt'], $cancelled);
                if ($evidence['paused'] ?? false) {
                    AssistantTurn::whereKey($turn->id)->where('status', 'running')->where('run_uuid', $runId)->update(['status' => 'paused', 'progress' => $evidence['progress'], 'error' => $evidence['progress'], 'finished_at' => now()]);

                    return;
                }
                $context = $this->context->build($exercise, $turn->mode, $turn, $evidence);
                if ($cancelled()) {
                    return;
                }
                $turn->update(['prompt' => $context['prompt'], 'context_manifest' => $context['manifest'], 'stage' => 'responding', 'progress' => 'Writing a response with the recorded source coverage.']);
            }
            $text = '';
            $lastWrite = 0;
            $this->providers->stream($connection, $turn->prompt, function (string $delta) use (&$text, &$lastWrite, $response): void {
                $text .= $delta;
                if (microtime(true) - $lastWrite > 0.25) {
                    $response->update(['content' => $text]);
                    $lastWrite = microtime(true);
                }
            }, $cancelled);
            $response->update(['content' => $text]);
            if ($turn->fresh()->status !== 'running' || $turn->fresh()->run_uuid !== $runId) {
                return;
            }
            $proposal = null;
            if (in_array($turn->mode, ['collect', 'naming'], true)) {
                $proposal = $this->proposals->decode($text);
                foreach ($proposal['ideas'] as &$idea) {
                    if (isset($idea['notes_md'])) {
                        $idea['notes_md'] = $this->reading->citations($turn, $idea['notes_md']);
                    }
                }
                unset($idea);
                $response->update(['content' => $this->reading->citations($turn, $proposal['reply'])]);
            }
            if ($proposal === null) {
                $response->update(['content' => $this->reading->citations($turn, $text)]);
            }
            // Cancellation can arrive while the final response is being decoded.
            AssistantTurn::whereKey($turn->id)->where('status', 'running')->where('run_uuid', $runId)->update([
                'status' => 'completed', 'finished_at' => now(),
                'proposals' => $proposal ? json_encode($proposal, JSON_THROW_ON_ERROR) : null,
            ]);
        } catch (\Throwable $exception) {
            if (isset($text)) {
                $response->update(['content' => $text]);
            }
            $message = $exception instanceof ValidationException ? collect($exception->errors())->flatten()->first() : (get_class($exception) === \RuntimeException::class ? $exception->getMessage() : 'The response could not finish. Check the connection and retry explicitly.');
            AssistantTurn::whereKey($turn->id)->where('status', 'running')->where('run_uuid', $runId)->update(['status' => 'failed', 'error' => mb_substr($message, 0, 1000), 'finished_at' => now()]);
        }
    }
}
