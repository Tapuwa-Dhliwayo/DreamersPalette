<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;

class WorldComparison extends Model
{
    use ArchivesStudioRecords, RevisesWorldRecord;

    protected $table = 'studio_world_comparisons';

    protected $fillable = ['title', 'criteria', 'subjects', 'notes_md'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'criteria' => 'array', 'subjects' => 'array'];
    }
}
