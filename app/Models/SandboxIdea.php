<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SandboxIdea extends Model
{
    protected $table = 'studio_sandbox_ideas';

    protected $fillable = [
        'kind',
        'title',
        'notes_md',
        'uncertainty',
        'alternative',
        'lane',
        'position',
        'date_label',
        'fields',
        'target_key',
        'target_revision',
        'source_message_id',
        'origin',
        'provenance',
        'source_token',
        'x',
        'y',
    ];

    protected function casts(): array
    {
        return [
            'provenance' => 'array',
            'fields' => 'array',
            'revision' => 'integer',
            'target_revision' => 'integer',
            'canonised_at' => 'datetime',
            'position' => 'decimal:3',
            'x' => 'integer',
            'y' => 'integer',
        ];
    }

    use ArchivesStudioRecords;
    use RevisesWorldRecord;

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(WorldExercise::class, 'exercise_id');
    }

    public function provenanceForReview(): array
    {
        $provenance = $this->provenance ?? [];
        $coverage = $provenance['coverage_status'] ?? null;

        if ($coverage !== null) {
            $hasVerdict = array_key_exists('recorded', $coverage) || array_key_exists('partial', $coverage);
            if (! $hasVerdict) {
                $provenance['coverage_status'] = AssistantTurn::UNRECORDED_COVERAGE;
            }

            return $provenance;
        }

        if (isset($provenance['turn_id'])) {
            $provenance['coverage_status'] = $this->exercise->turns()
                ->find($provenance['turn_id'])?->evidenceLimitations() ?? AssistantTurn::UNRECORDED_COVERAGE;

            return $provenance;
        }

        if ($this->origin !== 'assistant') {
            return $provenance;
        }

        $provenance['coverage_status'] = AssistantTurn::UNRECORDED_COVERAGE;

        return $provenance;
    }

    public function canEdit(): bool
    {
        return $this->canonised_at === null && ! $this->trashed();
    }
}
