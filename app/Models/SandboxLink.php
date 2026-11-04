<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SandboxLink extends Model
{
    protected $table = 'studio_sandbox_links';

    protected $fillable = ['source_id', 'target_id', 'kind', 'label', 'notes_md'];

    protected function casts(): array
    {
        return ['source_id' => 'integer', 'target_id' => 'integer', 'revision' => 'integer'];
    }
}
