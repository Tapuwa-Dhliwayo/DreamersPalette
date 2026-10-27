<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class MapVersion extends Model
{
    protected $table = 'studio_map_versions';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'revision' => 'integer', 'created_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Map versions are retained.'));
        static::deleting(fn () => throw new LogicException('Map versions are retained.'));
    }
}
