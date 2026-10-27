<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MagicVariation extends Model
{
    protected $table = 'studio_magic_variations';

    protected $fillable = ['mode_id', 'text_md', 'reason_md'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'source_revision' => 'integer', 'source_rule' => 'array'];
    }

    public function mode(): BelongsTo
    {
        return $this->belongsTo(MagicVariationMode::class, 'mode_id');
    }

    public function hasSourceChanges(MagicRule $rule): bool
    {
        return $this->source_revision !== $rule->revision;
    }
}
