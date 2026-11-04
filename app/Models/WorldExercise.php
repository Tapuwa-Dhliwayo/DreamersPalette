<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorldExercise extends Model
{
    protected $table = 'studio_world_exercises';

    protected $fillable = ['title', 'purpose', 'instructions', 'summary', 'summary_through_id', 'context_keys', 'passages', 'connection_id'];

    protected function casts(): array
    {
        return ['world_id' => 'integer', 'summary_through_id' => 'integer', 'connection_id' => 'integer', 'context_keys' => 'array', 'passages' => 'array', 'revision' => 'integer'];
    }

    use ArchivesStudioRecords;
    use RevisesWorldRecord;

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function ideas(): HasMany
    {
        return $this->hasMany(SandboxIdea::class, 'exercise_id')->orderBy('position')->orderBy('id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AssistantMessage::class, 'exercise_id')->orderBy('id');
    }

    public function turns(): HasMany
    {
        return $this->hasMany(AssistantTurn::class, 'exercise_id')->orderByDesc('id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(SandboxLink::class, 'exercise_id');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(CanonChange::class, 'exercise_id')->latest('id');
    }
}
