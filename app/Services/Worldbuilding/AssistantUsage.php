<?php

namespace App\Services\Worldbuilding;

use App\Models\AssistantConnection;
use App\Models\AssistantRequest;
use App\Models\AssistantTurn;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class AssistantUsage
{
    public function begin(AssistantConnection $connection, AssistantTurn $turn, string $purpose): AssistantRequest
    {
        return DB::transaction(function () use ($connection, $turn, $purpose): AssistantRequest {
            $user = User::whereKey($connection->user_id)->lockForUpdate()->firstOrFail();
            $saved = AssistantConnection::lockForUpdate()->findOrFail($connection->id);
            $current = AssistantTurn::lockForUpdate()->findOrFail($turn->id);
            Gate::forUser($user)->authorize('update', $saved);
            Gate::forUser($user)->authorize('update', $current->exercise);
            if (
                $current->connection_id !== $saved->id
                || $current->exercise->world->author_id !== $saved->user_id
                || ! $saved->enabled
                || $saved->revision !== $connection->revision
                || $current->status !== 'running'
                || $current->run_uuid !== $turn->run_uuid
            ) {
                throw new RuntimeException('This connection or request is no longer available. Your question and completed reading are saved.');
            }
            $daily = AssistantRequest::where('connection_id', $saved->id)->where('created_at', '>=', now()->utc()->startOfDay())->count();
            if ($daily >= $saved->daily_request_limit || AssistantRequest::where('turn_id', $turn->id)->count() >= $saved->turn_request_limit) {
                throw new RuntimeException('Your request budget is reached. Reading and your question are saved. Adjust the connection budget or retry after the daily reset; no paid fallback was used.');
            }

            return AssistantRequest::create([
                'user_id' => $saved->user_id,
                'connection_id' => $saved->id,
                'turn_id' => $turn->id,
                'run_uuid' => $turn->run_uuid,
                'provider' => $saved->provider,
                'model' => $connection->model,
                'purpose' => $purpose,
                'pricing' => ! $saved->isLocal()
                    && $connection->model === $saved->model
                    && $saved->input_rate_usd !== null
                    && $saved->output_rate_usd !== null
                        ? $saved->only(['input_rate_usd', 'output_rate_usd'])
                        : null,
            ]);
        });
    }

    public function summary(int $userId, ?int $exerciseId = null, ?int $connectionId = null): array
    {
        $requests = AssistantRequest::where('user_id', $userId)
            ->when($exerciseId, fn ($query) => $query->whereIn('turn_id', AssistantTurn::where('exercise_id', $exerciseId)->select('id')))
            ->when($connectionId, fn ($query) => $query->where('connection_id', $connectionId));
        $totals = (clone $requests)
            ->selectRaw('COUNT(*) AS requests, COUNT(input_tokens) AS input_reported, COUNT(output_tokens) AS output_reported, COUNT(cache_read_tokens) AS cache_reported, SUM(cache_read_tokens) AS cache_read_tokens, SUM(cache_write_tokens) AS cache_write_tokens, SUM(input_tokens) AS input_tokens, SUM(output_tokens) AS output_tokens, COUNT(reported_cost_usd) AS cost_reported, SUM(reported_cost_usd) AS cost_usd, COUNT(estimated_cost_usd) AS estimated_reported, SUM(estimated_cost_usd) AS estimated_cost_usd')
            ->first()
            ->toArray();
        $totals['reading_requests'] = (clone $requests)->where('purpose', 'reading')->count();
        $totals['failed_requests'] = (clone $requests)->whereIn('status', ['failed', 'cancelled', 'interrupted'])->count();
        $totals['daily_requests'] = (clone $requests)->where('created_at', '>=', now()->utc()->startOfDay())->count();

        return $totals;
    }
}
