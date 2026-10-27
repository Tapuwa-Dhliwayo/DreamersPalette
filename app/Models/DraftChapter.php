<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class DraftChapter extends Model
{
    public $timestamps = false;

    protected $table = 'studio_draft_chapters';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Finished draft chapters are immutable.'));
        static::deleting(fn () => throw new LogicException('Finished draft chapters are retained.'));
    }
}
