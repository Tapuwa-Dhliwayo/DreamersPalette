<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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

    public function poemConversation(): BelongsTo
    {
        return $this->belongsTo(PoemAnalysisConversation::class, 'poem_conversation_id');
    }

    public function subject(): ?Model
    {
        return $this->poem_conversation_id ? $this->poemConversation : $this->exercise;
    }

    public function authorId(): ?int
    {
        return $this->poem_conversation_id ? $this->poemConversation?->author_id : $this->exercise?->world?->author_id;
    }

    // Conversation titles repeat across poems ("Analysis 1"), so poetry activity is named by its poem first.
    public function activityTitle(): string
    {
        return $this->poem_conversation_id
            ? $this->poemConversation->poem->title.' · '.$this->poemConversation->title
            : $this->exercise->title;
    }

    public function activityUrl(): string
    {
        return $this->poem_conversation_id
            ? '/dashboard/poems/'.$this->poemConversation->poem_id.'/analysis?conversation='.$this->poem_conversation_id
            : route('studio.world.sandbox', ['world' => $this->exercise->world_id, 'exercise' => $this->exercise_id], absolute: false);
    }

    public static function outstandingFor(User $author): Collection
    {
        return self::forAuthor($author->id)->with(['exercise', 'poemConversation.poem'])
            ->where(fn ($query) => $query->active()->orWhereNull('acknowledged_at'))
            ->latest('updated_at')->get()
            ->filter(fn ($turn) => $turn->subject() && Gate::forUser($author)->allows('update', $turn->subject()));
    }

    public function stop(): void
    {
        DB::transaction(function (): void {
            $current = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if (! $current->isActive()) {
                return;
            }
            $current->update(['status' => 'cancelled', 'finished_at' => now()]);
            $current->closeRequests('cancelled');
        });
    }

    private function closeRequests(string $status): void
    {
        AssistantRequest::where('turn_id', $this->id)->where('run_uuid', $this->run_uuid)->where('status', 'running')
            ->update(['status' => $status, 'finished_at' => now()]);
    }

    public static function expireStalled(Builder $scope): void
    {
        $cutoff = now()->subSeconds(config('worldbuilding.timeout') + 60);
        $ids = (clone $scope)->active()->where('updated_at', '<', $cutoff)->limit(100)->pluck('id');
        foreach ($ids as $id) {
            DB::transaction(function () use ($id, $cutoff): void {
                $turn = self::whereKey($id)->lockForUpdate()->first();
                if (! $turn || ! $turn->isActive() || $turn->updated_at >= $cutoff) {
                    return;
                }
                $turn->update(['status' => 'failed', 'error' => $turn->interruptionMessage(), 'finished_at' => now()]);
                $turn->closeRequests('interrupted');
            });
        }
    }

    private function interruptionMessage(): string
    {
        return match (true) {
            $this->poem_conversation_id !== null => 'The response was interrupted. Your question and sources are saved. Open the conversation to retry.',
            default => 'This response was interrupted or timed out. Your question and completed reading are saved. Retry this request to continue.',
        };
    }

    public function failRun(string $runId, string $message, ?string $receivedText = null): void
    {
        DB::transaction(function () use ($runId, $message, $receivedText): void {
            $current = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if ($current->status !== 'running' || $current->run_uuid !== $runId) {
                return;
            }
            if ($receivedText !== null && $current->response_id !== null) {
                AssistantMessage::whereKey($current->response_id)->update(['content' => $receivedText]);
            }
            $current->update(['status' => 'failed', 'error' => $message, 'finished_at' => now()]);
            $current->closeRequests('interrupted');
        });
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
        $query->where(function ($owned) use ($userId): void {
            $owned->whereHas('exercise.world', fn ($world) => $world->where('author_id', $userId))
                ->orWhereHas('poemConversation', fn ($conversation) => $conversation->where('author_id', $userId));
        });
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
