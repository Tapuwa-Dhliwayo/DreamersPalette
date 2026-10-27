<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WorldEvent extends Model
{
    use ArchivesStudioRecords;
    use RevisesWorldRecord;

    protected $table = 'studio_world_events';

    protected $fillable = ['title', 'date_label', 'position', 'notes_md', 'calendar_id', 'calendar_day'];

    protected function casts(): array
    {
        return ['calendar_id' => 'integer', 'calendar_day' => 'integer', 'world_id' => 'integer', 'revision' => 'integer', 'position' => 'decimal:3'];
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(WorldCalendar::class, 'calendar_id')->withTrashed();
    }

    public function calendarLabel(): ?string
    {
        return $this->calendar_day !== null ? $this->calendar?->label($this->calendar_day) : null;
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(WorldPerson::class, 'studio_world_event_people', 'event_id', 'person_id')->withTrashed();
    }
}
