<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorldEntryType extends Model
{
    protected $table = 'studio_entry_types';

    public $timestamps = false;

    protected $guarded = ['id'];
}
