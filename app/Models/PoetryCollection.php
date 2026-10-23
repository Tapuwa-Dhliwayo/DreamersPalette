<?php

namespace App\Models;

use App\Models\Concerns\HasPreciseTimestamps;
use App\Models\Concerns\PublishesContent;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PoetryCollection extends Model
{
    use HasPreciseTimestamps, HasUuids, PublishesContent, SoftDeletes;

    protected $fillable = ['title', 'description', 'theme_background_url', 'theme_overlay_opacity', 'theme_text_mode', 'accent_color', 'theme_text_color', 'theme_heading_color', 'theme_muted_color'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'theme_overlay_opacity' => 'float',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'author_id');
    }

    public function poems(): HasMany
    {
        return $this->hasMany(Poem::class, 'collection_id');
    }
}
