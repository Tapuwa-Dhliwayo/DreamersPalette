<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class MagicVersion extends Model
{
    protected $table = 'studio_magic_versions';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'snapshot' => 'array', 'created_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Magic system versions are immutable.'));
        static::deleting(fn () => throw new LogicException('Magic system versions are retained.'));
    }
}
