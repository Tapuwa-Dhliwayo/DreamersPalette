<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;

class WorldInvestigation extends Model
{
    use ArchivesStudioRecords, RevisesWorldRecord;

    protected $table = 'studio_world_investigations';

    protected $fillable = ['updated_by', 'title', 'subjects', 'question_md', 'evidence', 'open_questions_md', 'conclusion_md', 'is_resolved'];

    protected function casts(): array
    {
        return ['subjects' => 'array', 'evidence' => 'array', 'is_resolved' => 'boolean', 'revision' => 'integer'];
    }
}
