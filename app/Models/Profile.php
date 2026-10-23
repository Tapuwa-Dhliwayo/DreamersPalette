<?php

namespace App\Models;

use App\Models\Concerns\HasPreciseTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profile extends Model
{
    use HasPreciseTimestamps;

    public const UPDATED_AT = null;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['display_name', 'bio', 'avatar_url'];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id');
    }

    public function collections(): HasMany
    {
        return $this->hasMany(PoetryCollection::class, 'author_id');
    }

    public function poems(): HasMany
    {
        return $this->hasMany(Poem::class, 'author_id');
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'author_id');
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class, 'author_id');
    }

    public function generatedAssets(): HasMany
    {
        return $this->hasMany(GeneratedAsset::class, 'author_id');
    }
}
