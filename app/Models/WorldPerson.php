<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WorldPerson extends Model
{
    use ArchivesStudioRecords;
    use RevisesWorldRecord;

    protected $table = 'studio_world_people';

    protected $fillable = ['creation_uuid', 'name', 'aliases', 'roles', 'domain', 'notes_md'];

    protected function casts(): array
    {
        return ['world_id' => 'integer', 'revision' => 'integer', 'aliases' => 'array', 'roles' => 'array'];
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(WorldEvent::class, 'studio_world_event_people', 'person_id', 'event_id')->withTrashed()->orderBy('position');
    }
}
