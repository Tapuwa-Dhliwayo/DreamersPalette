<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorldKnowledge extends Model
{
    use ArchivesStudioRecords, RevisesWorldRecord;

    protected $table = 'studio_world_knowledge';

    protected $fillable = ['updated_by', 'title', 'truth_md', 'knowers', 'manuscript_id', 'reveal_chapter_id', 'notes_md'];

    protected function casts(): array
    {
        return ['knowers' => 'array', 'revision' => 'integer', 'manuscript_id' => 'integer', 'reveal_chapter_id' => 'integer'];
    }

    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class)->withTrashed();
    }

    public function revealChapter(): BelongsTo
    {
        return $this->belongsTo(ManuscriptChapter::class, 'reveal_chapter_id')->withTrashed();
    }
}
