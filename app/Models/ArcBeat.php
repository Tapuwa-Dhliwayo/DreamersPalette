<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArcBeat extends Model
{
    use ArchivesStudioRecords;
    use RevisesWorldRecord;

    protected $table = 'studio_arc_beats';

    protected $fillable = ['title', 'event_id', 'position', 'intensity', 'notes_md'];

    protected function casts(): array
    {
        return ['arc_id' => 'integer', 'revision' => 'integer', 'event_id' => 'integer', 'position' => 'decimal:3', 'intensity' => 'integer'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(WorldEvent::class)->withTrashed();
    }
}
