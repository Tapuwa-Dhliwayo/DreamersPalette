<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AssistantRequest extends Model
{
    protected $table = 'studio_assistant_requests';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'pricing' => 'array',
            'estimated_cost_usd' => 'decimal:8',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'cache_read_tokens' => 'integer',
            'cache_write_tokens' => 'integer',
            'reported_cost_usd' => 'decimal:8',
            'finished_at' => 'datetime',
        ];
    }

    public function finish(string $status): void
    {
        DB::transaction(function () use ($status): void {
            $current = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            $fields = ['estimated_cost_usd' => $current->estimatedCost()];
            if ($current->status === 'running') {
                $fields += ['status' => $status, 'finished_at' => now()];
            }
            $current->update($fields);
        });
    }

    private function estimatedCost(): ?float
    {
        if (! $this->pricing || $this->input_tokens === null || $this->output_tokens === null) {
            return null;
        }
        $input = $this->input_tokens;
        if (in_array($this->provider, ['anthropic', 'claude'], true)) {
            $input += ($this->cache_read_tokens ?? 0) + ($this->cache_write_tokens ?? 0);
        }

        return ($input * $this->pricing['input_rate_usd'] + $this->output_tokens * $this->pricing['output_rate_usd']) / 1000000;
    }

    public function recordUsage(array $usage): void
    {
        // Stream events can repeat cumulative totals; never add a snapshot twice.
        $fields = [];
        foreach (['input_tokens', 'output_tokens', 'cache_read_tokens', 'cache_write_tokens', 'reported_cost_usd'] as $key) {
            if (isset($usage[$key]) && is_numeric($usage[$key]) && $usage[$key] >= 0) {
                $fields[$key] = $usage[$key];
            }
        }
        if ($fields) {
            $this->fill($fields);
            $this->update([...$fields, 'estimated_cost_usd' => $this->estimatedCost()]);
        }
    }
}
