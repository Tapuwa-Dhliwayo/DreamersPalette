<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssistantTurn extends Model
{
    protected $table = 'studio_assistant_turns';

    protected $fillable = ['connection_id', 'message_id', 'response_id', 'request_uuid', 'provider', 'model', 'mode', 'status', 'context_manifest', 'prompt', 'proposals', 'error', 'started_at', 'finished_at', 'request_context', 'reading_depth', 'stage', 'progress', 'run_uuid', 'acknowledged_at'];

    protected function casts(): array
    {
        return ['request_context' => 'array', 'context_manifest' => 'array', 'proposals' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime', 'acknowledged_at' => 'datetime'];
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(WorldExercise::class, 'exercise_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(AssistantSource::class, 'turn_id')->orderBy('position')->orderBy('id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['queued', 'running'], true);
    }
}
