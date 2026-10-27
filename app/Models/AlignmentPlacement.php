<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlignmentPlacement extends Model
{
    use ArchivesStudioRecords;
    use RevisesWorldRecord;

    protected $table = 'studio_alignment_placements';

    protected $fillable = ['name', 'person_id', 'system_id', 'x', 'y', 'reason_md'];

    protected function casts(): array
    {
        return ['chart_id' => 'integer', 'revision' => 'integer', 'person_id' => 'integer', 'system_id' => 'integer', 'x' => 'integer', 'y' => 'integer'];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(WorldPerson::class)->withTrashed();
    }

    public function system(): BelongsTo
    {
        return $this->belongsTo(MagicSystem::class, 'system_id')->withTrashed();
    }
}
