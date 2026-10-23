<?php

namespace App\Models;

use App\Models\Concerns\HasPreciseTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedAsset extends Model
{
    use HasPreciseTimestamps, HasUuids;

    public const UPDATED_AT = null;

    protected $hidden = ['source_uuid'];

    protected $fillable = ['type', 'prompt', 'image_url'];

    protected function casts(): array
    {
        return [
            'author_id' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
