<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantMessage extends Model
{
    protected $table = 'studio_assistant_messages';

    protected $fillable = ['role', 'content'];

    protected function casts(): array
    {
        return [];
    }
}
