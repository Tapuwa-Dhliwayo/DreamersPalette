<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlignmentChart extends Model
{
    use ArchivesStudioRecords;
    use RevisesWorldRecord;

    protected $table = 'studio_alignment_charts';

    protected $fillable = ['title', 'left_label', 'right_label', 'top_label', 'bottom_label', 'notes_md'];

    protected function casts(): array
    {
        return ['world_id' => 'integer', 'revision' => 'integer'];
    }

    public function placements(): HasMany
    {
        return $this->hasMany(AlignmentPlacement::class, 'chart_id')->orderBy('name');
    }
}
