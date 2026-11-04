<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SandboxIdea extends Model
{
    protected $table = 'studio_sandbox_ideas';

    protected $fillable = ['kind', 'title', 'notes_md', 'uncertainty', 'alternative', 'lane', 'position', 'date_label', 'fields', 'target_key', 'target_revision', 'source_message_id', 'origin', 'source_token', 'x', 'y'];

    protected function casts(): array
    {
        return ['fields' => 'array', 'revision' => 'integer', 'target_revision' => 'integer', 'canonised_at' => 'datetime', 'position' => 'decimal:3', 'x' => 'integer', 'y' => 'integer'];
    }

    use ArchivesStudioRecords;
    use RevisesWorldRecord;

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(WorldExercise::class, 'exercise_id');
    }

    public function canEdit(): bool
    {
        return $this->canonised_at === null && ! $this->trashed();
    }
}
