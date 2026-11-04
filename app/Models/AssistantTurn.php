<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantTurn extends Model
{
    protected $table = 'studio_assistant_turns';

    protected $fillable = ['connection_id', 'message_id', 'response_id', 'request_uuid', 'provider', 'model', 'mode', 'status', 'context_manifest', 'prompt', 'proposals', 'error', 'started_at', 'finished_at'];

    protected function casts(): array
    {
        return ['context_manifest' => 'array', 'proposals' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(WorldExercise::class, 'exercise_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['queued', 'running'], true);
    }
}
