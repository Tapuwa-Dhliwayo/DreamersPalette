<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantConnection extends Model
{
    protected $table = 'studio_assistant_connections';

    protected $fillable = ['name', 'provider', 'model', 'credential', 'enabled'];

    protected function casts(): array
    {
        return ['credential' => 'encrypted', 'enabled' => 'boolean', 'revision' => 'integer', 'user_id' => 'integer'];
    }

    protected $hidden = ['credential'];

    public function isLocal(): bool
    {
        return in_array($this->provider, ['codex', 'claude'], true);
    }
}
