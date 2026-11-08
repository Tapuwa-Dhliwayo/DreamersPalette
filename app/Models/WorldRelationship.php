<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;

class WorldRelationship extends Model
{
    use ArchivesStudioRecords, RevisesWorldRecord;

    protected $table = 'studio_world_relationships';

    protected $fillable = [
        'source_key',
        'source_name',
        'target_key',
        'target_name',
        'label',
        'period',
        'notes_md',
        'is_dependency',
        'is_mutual',
    ];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'is_dependency' => 'boolean', 'is_mutual' => 'boolean'];
    }

    // Labels can carry raw source citations that are noise outside the editor.
    public function displayLabel(): string
    {
        return trim(preg_replace('/\s*\[source:\d+:\d+-\d+\]/', '', $this->label));
    }
}
