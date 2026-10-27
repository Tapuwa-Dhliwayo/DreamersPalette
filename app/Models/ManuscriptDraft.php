<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class ManuscriptDraft extends Model
{
    public $timestamps = false;

    protected $table = 'studio_drafts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['manuscript_id' => 'integer', 'world_context' => 'array', 'undone_at' => 'immutable_datetime', 'number' => 'integer', 'source_revision' => 'integer', 'created_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Finished manuscript drafts are immutable.'));
        static::deleting(fn () => throw new LogicException('Finished manuscript drafts are retained.'));
    }

    public function recordUndo(User $author): void
    {
        // Content stays immutable; only this explicit cancellation marker may change.
        $this->newModelQuery()->whereKey($this->id)->whereNull('undone_at')->update([
            'undone_at' => now(), 'undone_by' => $author->id,
        ]);
    }

    public function manuscript(): BelongsTo
    {
        return $this->belongsTo(Manuscript::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(DraftChapter::class, 'draft_id')->orderBy('position')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
