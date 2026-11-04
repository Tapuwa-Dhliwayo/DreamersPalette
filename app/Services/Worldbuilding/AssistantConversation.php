<?php

namespace App\Services\Worldbuilding;

use App\Models\AssistantConnection;
use App\Models\AssistantTurn;
use App\Models\User;
use App\Models\World;
use App\Models\WorldExercise;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class AssistantConversation
{
    public function __construct(private ExerciseContext $context, private AssistantProviders $providers, private SandboxProposals $proposals) {}

    public function send(WorldExercise $exercise, AssistantConnection $connection, string $content, string $mode, string $requestUuid): AssistantTurn
    {
        Gate::authorize('update', $exercise);
        Gate::authorize('update', $connection);
        $turn = DB::transaction(function () use ($exercise, $connection, $content, $mode, $requestUuid): AssistantTurn {
            World::lockForUpdate()->findOrFail($exercise->world_id);
            $exercise = WorldExercise::lockForUpdate()->findOrFail($exercise->id);
            Gate::authorize('update', $exercise);
            if ($existing = $exercise->turns()->where('request_uuid', $requestUuid)->first()) {
                return $existing;
            }
            if (! $connection->enabled) {
                throw ValidationException::withMessages(['conversationForm.content' => 'Reconnect this provider before sending.']);
            }
            if ($exercise->turns()->whereIn('status', ['queued', 'running'])->exists()) {
                throw ValidationException::withMessages(['conversationForm.content' => 'Stop or finish the current response before sending another.']);
            }
            $message = $exercise->messages()->create(['role' => 'user', 'content' => $content]);
            $context = $this->context->build($exercise, $mode);
            $turn = $exercise->turns()->create(['connection_id' => $connection->id, 'message_id' => $message->id, 'request_uuid' => $requestUuid, 'provider' => $connection->provider, 'model' => $connection->model, 'mode' => $mode, 'context_manifest' => $context['manifest'], 'prompt' => $context['prompt']]);
            $exercise->forceFill(['connection_id' => $connection->id])->save();

            return $turn;
        });
        if ($turn->status === 'queued') {
            $command = 'nohup '.escapeshellarg(PHP_BINARY).' '.escapeshellarg(base_path('artisan')).' studio:assistant-turn '.(int) $turn->id.' > /dev/null 2>&1 < /dev/null &';
            Process::fromShellCommandline($command, base_path())->run();
        }

        return $turn;
    }

    public function run(AssistantTurn $turn): void
    {
        $claimed = AssistantTurn::whereKey($turn->id)->where('status', 'queued')->update(['status' => 'running', 'started_at' => now()]);
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
            if ($connection->provider !== $turn->provider || $connection->model !== $turn->model) {
                throw new \RuntimeException('The connection changed before the turn started. Send a new request.');
            }
            $text = '';
            $lastWrite = 0;
            $this->providers->stream($connection, $turn->prompt, function (string $delta) use (&$text, &$lastWrite, $response): void {
                $text .= $delta;
                if (microtime(true) - $lastWrite > 0.25) {
                    $response->update(['content' => $text]);
                    $lastWrite = microtime(true);
                }
            }, fn () => $turn->fresh()->status !== 'running' || ! $connection->fresh()->enabled || ! $exercise->fresh() || ! Gate::forUser($user)->allows('update', $exercise->fresh()));
            $response->update(['content' => $text]);
            if ($turn->fresh()->status !== 'running') {
                return;
            }
            if ($turn->mode !== 'conversation') {
                $proposal = $this->proposals->decode($text);
                $turn->proposals = $proposal;
                $response->update(['content' => $proposal['reply']]);
            }
            $turn->fill(['status' => 'completed', 'finished_at' => now()])->save();
        } catch (\Throwable $exception) {
            if (isset($text)) {
                $response->update(['content' => $text]);
            }
            $message = $exception instanceof ValidationException ? collect($exception->errors())->flatten()->first() : ($exception instanceof \RuntimeException ? $exception->getMessage() : 'The response could not finish. Check the connection and retry explicitly.');
            AssistantTurn::whereKey($turn->id)->where('status', 'running')->update(['status' => 'failed', 'error' => mb_substr($message, 0, 1000), 'finished_at' => now()]);
        }
    }
}
