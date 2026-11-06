<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class AssistantTurn extends Model
{
    public const UNRECORDED_COVERAGE = ['recorded' => false];

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

    public function message(): BelongsTo
    {
        return $this->belongsTo(AssistantMessage::class, 'message_id');
    }

    public function question(): string
    {
        return $this->message()->firstOrFail()->content;
    }

    public function sources(): HasMany
    {
        return $this->hasMany(AssistantSource::class, 'turn_id')->orderBy('position')->orderBy('id');
    }

    public function usesStructuredReply(): bool
    {
        // Turns saved before response_contract 2 signalled structured replies by mode alone, so these modes must stay.
        return ($this->request_context['response_contract'] ?? 0) >= 2 || in_array($this->mode, ['collect', 'naming', 'encyclopedia'], true);
    }

    public function citationSources(): Collection
    {
        $ids = array_unique([$this->id, ...($this->request_context['recovery']['citation_turn_ids'] ?? [])]);

        return AssistantSource::whereIn('turn_id', $this->exercise->turns()->whereIn('id', $ids)->select('id'))->orderBy('position')->get();
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', ['queued', 'running']);
    }

    public function scopeForAuthor(Builder $query, int $userId): void
    {
        $query->whereHas('exercise.world', fn ($world) => $world->where('author_id', $userId));
    }

    // A status without a `partial` verdict was never really recorded, whatever else it carries.
    public static function coverageOrUnrecorded(array $status): array
    {
        return array_key_exists('partial', $status) ? $status : self::UNRECORDED_COVERAGE;
    }

    public function evidenceLimitations(): array
    {
        $status = self::coverageOrUnrecorded($this->context_manifest['coverage_status'] ?? []);

        if ($status === self::UNRECORDED_COVERAGE) {
            return $status;
        }

        return ['recorded' => $status['recorded'] ?? true] + collect($status)->only([
            'partial',
            'scope',
            'stop_reason',
            'inherited_stop_reason',
            'unfetched_subjects',
            'context_sections_omitted',
        ])->all() + [
            'omitted_notes_count' => $status['omitted_notes_count'] ?? count($status['omitted_notes'] ?? []),
            'pruned_retrieval_count' => $status['pruned_retrieval_count'] ?? count($status['pruned_retrieval'] ?? []),
        ];
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['queued', 'running'], true);
    }
}
