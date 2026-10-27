<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;

class WorldRelationship extends Model
{
    use ArchivesStudioRecords, RevisesWorldRecord;

    protected $table = 'studio_world_relationships';

    protected $fillable = ['source_key', 'source_name', 'target_key', 'target_name', 'label', 'period', 'notes_md', 'is_dependency'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'is_dependency' => 'boolean'];
    }
}
