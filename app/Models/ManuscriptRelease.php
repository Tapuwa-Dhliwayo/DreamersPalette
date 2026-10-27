<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class ManuscriptRelease extends Model
{
    protected $table = 'studio_releases';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'created_at' => 'immutable_datetime', 'number' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Public releases are retained.'));
        static::deleting(fn () => throw new LogicException('Public releases are retained.'));
    }
}
