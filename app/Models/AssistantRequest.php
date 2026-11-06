<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        $estimate = null;
        if ($this->pricing && $this->input_tokens !== null && $this->output_tokens !== null) {
            $input = $this->input_tokens;
            if (in_array($this->provider, ['anthropic', 'claude'], true)) {
                $input += ($this->cache_read_tokens ?? 0) + ($this->cache_write_tokens ?? 0);
            }
            $estimate = ($input * $this->pricing['input_rate_usd'] + $this->output_tokens * $this->pricing['output_rate_usd']) / 1000000;
        }
        $this->update(['status' => $status, 'estimated_cost_usd' => $estimate, 'finished_at' => now()]);
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
            $this->update($fields);
        }
    }
}
