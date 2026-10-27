<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorldEntry extends Model
{
    use ArchivesStudioRecords, RevisesWorldRecord;

    protected $table = 'studio_world_entries';

    protected $fillable = ['name', 'type_id', 'aliases', 'notes_md', 'creation_uuid'];

    protected function casts(): array
    {
        return ['world_id' => 'integer', 'type_id' => 'integer', 'revision' => 'integer', 'aliases' => 'array'];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(WorldEntryType::class, 'type_id');
    }
}
