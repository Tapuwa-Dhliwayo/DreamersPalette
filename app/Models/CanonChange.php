<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CanonChange extends Model
{
    protected $table = 'studio_canon_changes';

    protected $fillable = ['user_id', 'request_uuid', 'changes'];

    protected function casts(): array
    {
        return ['changes' => 'array', 'reversed_at' => 'datetime'];
    }
}
