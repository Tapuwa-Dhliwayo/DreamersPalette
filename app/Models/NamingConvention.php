<?php

namespace App\Models;

use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;

class NamingConvention extends Model
{
    protected $table = 'studio_naming_conventions';

    protected $fillable = ['name', 'guidance', 'examples', 'source', 'reviewed'];

    protected function casts(): array
    {
        return ['reviewed' => 'boolean', 'revision' => 'integer'];
    }

    use RevisesWorldRecord;
}
