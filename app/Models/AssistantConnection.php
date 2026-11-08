<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantConnection extends Model
{
    protected $table = 'studio_assistant_connections';

    protected $fillable = [
        'name',
        'provider',
        'model',
        'credential',
        'enabled',
        'daily_request_limit',
        'turn_request_limit',
        'input_rate_usd',
        'output_rate_usd',
    ];

    protected function casts(): array
    {
        return [
            'input_rate_usd' => 'decimal:6',
            'output_rate_usd' => 'decimal:6',
            'credential' => 'encrypted',
            'enabled' => 'boolean',
            'daily_request_limit' => 'integer',
            'turn_request_limit' => 'integer',
            'allowance' => 'array',
            'allowance_checked_at' => 'datetime',
            'revision' => 'integer',
            'user_id' => 'integer',
        ];
    }

    protected $hidden = ['credential'];

    public function usageUrl(): string
    {
        return match ($this->provider) {
            'codex' => 'https://chatgpt.com/codex/settings/usage',
            'claude' => 'https://claude.ai/settings/usage',
            'openai' => 'https://platform.openai.com/usage',
            'anthropic' => 'https://console.anthropic.com/settings/usage',
            'openrouter' => 'https://openrouter.ai/settings/credits',
            default => throw new \RuntimeException('Unsupported provider.'),
        };
    }

    public function isLocal(): bool
    {
        return in_array($this->provider, ['codex', 'claude'], true);
    }
}
