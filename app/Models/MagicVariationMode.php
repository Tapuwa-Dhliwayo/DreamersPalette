<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MagicVariationMode extends Model
{
    protected $table = 'studio_magic_variation_modes';

    public $timestamps = false;

    protected $guarded = ['id'];
}
