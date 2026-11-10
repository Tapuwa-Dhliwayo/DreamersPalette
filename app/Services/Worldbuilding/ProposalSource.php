<?php

namespace App\Services\Worldbuilding;

use App\Models\AssistantTurn;
use App\Models\ExchangeImport;
use App\Models\WorldExercise;

/**
 * Where a batch of proposals came from. Staging into the sandbox board is the same
 * work whether an assistant turn or an imported file produced it; only the origin's
 * own answers (revisions it vouches for, coverage, tokens, provenance) differ.
 */
class ProposalSource
{
    private function __construct(
        public readonly WorldExercise $exercise,
        private readonly ?AssistantTurn $turn,
        private readonly ?ExchangeImport $import,
    ) {}

    public static function forTurn(AssistantTurn $turn): self
    {
        return new self($turn->exercise, $turn, null);
    }

    public static function forImport(ExchangeImport $import, WorldExercise $exercise): self
    {
        return new self($exercise, null, $import);
    }

    public function turn(): AssistantTurn
    {
        return $this->turn ?? throw new \LogicException('This proposal source is not an assistant turn.');
    }

    public function origin(): string
    {
        return $this->turn ? 'assistant' : 'import';
    }

    /** The revision of the record the source says it read, which staging must still find current. */
    public function expectedRevision(string $targetKey, array $idea): ?int
    {
        if ($this->import) {
            return isset($idea['target_revision']) ? (int) $idea['target_revision'] : null;
        }
        $manifest = $this->turn->context_manifest;
        $revision = $manifest['proposal_targets'][$targetKey] ?? collect($manifest['records'] ?? [])->firstWhere('key', $targetKey)['revision'] ?? null;

        return $revision === null ? null : (int) $revision;
    }

    /** The revision of the pending proposal the source was shown when it refined it. */
    public function capturedProposalRevision(int $proposalId, array $idea): ?int
    {
        if ($this->import) {
            return isset($idea['proposal_revision']) ? (int) $idea['proposal_revision'] : null;
        }
        $captured = collect($this->turn->request_context['sandbox'] ?? [])->firstWhere('id', $proposalId);

        return $captured ? (int) $captured['revision'] : null;
    }

    public function citations(SourceReading $reading, string $text): string
    {
        return $this->turn ? $reading->citations($this->turn, $text) : $text;
    }

    public function coverage(): array
    {
        return $this->turn ? $this->turn->evidenceLimitations() : ['recorded' => false, 'external' => true];
    }

    public function sourceMessageId(): ?int
    {
        return $this->turn ? $this->turn->response_id : $this->import->messages()->value('id');
    }

    public function tokenPrefix(): string
    {
        return $this->turn ? 'turn:'.$this->turn->id : 'import:'.$this->import->id;
    }

    public function provenanceExtras(): array
    {
        if ($this->import) {
            return ['import_id' => $this->import->id, 'external_origin' => $this->import->external_origin];
        }

        return [
            'turn_id' => $this->turn->id,
            'recovery' => collect($this->turn->request_context['recovery'] ?? [])
                ->only(['message_id', 'idea_id', 'idea_revision', 'source_turn_id'])
                ->all(),
        ];
    }

    public function collectedIdeaIds(): array
    {
        return $this->turn
            ? ($this->turn->request_context['collected_proposals'] ?? [])
            : ($this->import->receipt['collected_ideas'] ?? []);
    }

    public function recordCollected(array $ideaIds): void
    {
        if ($this->import) {
            $this->import->update(['receipt' => array_replace($this->import->receipt ?? [], ['collected_ideas' => $ideaIds])]);

            return;
        }
        $settings = $this->turn->request_context ?? [];
        $settings['collected_proposals'] = $ideaIds;
        $this->turn->update(['request_context' => $settings]);
    }
}
