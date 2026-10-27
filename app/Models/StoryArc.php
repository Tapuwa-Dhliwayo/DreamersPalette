<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoryArc extends Model
{
    use ArchivesStudioRecords;
    use RevisesWorldRecord;

    protected $table = 'studio_story_arcs';

    protected $fillable = ['title', 'person_id', 'manuscript_id', 'measure', 'notes_md'];

    protected function casts(): array
    {
        return ['world_id' => 'integer', 'revision' => 'integer', 'person_id' => 'integer', 'manuscript_id' => 'integer'];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(WorldPerson::class)->withTrashed();
    }

    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class)->withTrashed();
    }

    public function beats(): HasMany
    {
        return $this->hasMany(ArcBeat::class, 'arc_id')->orderBy('position')->orderBy('id');
    }
}
