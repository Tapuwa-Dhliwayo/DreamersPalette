<?php

namespace App\Models;

use App\Models\Concerns\HasPreciseTimestamps;
use App\Models\Concerns\PublishesContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasPreciseTimestamps, PublishesContent, SoftDeletes;

    protected $hidden = ['source_uuid'];

    protected $fillable = ['title', 'synopsis', 'cover_image_url', 'ai_prompt', 'theme_background_url', 'theme_overlay_opacity', 'theme_text_mode', 'accent_color'];

    protected function casts(): array
    {
        return [
            'author_id' => 'integer',
            'is_published' => 'boolean',
            'theme_overlay_opacity' => 'float',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function isStudioManaged(): bool
    {
        return $this->manuscript()->exists();
    }

    public function manuscript(): HasOne
    {
        return $this->hasOne(Manuscript::class)->withTrashed();
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class, 'book_id');
    }
}
