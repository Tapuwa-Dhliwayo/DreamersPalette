<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MagicDecision extends Model
{
    protected $table = 'studio_magic_decisions';

    public $timestamps = false;

    protected $guarded = ['id'];
}
