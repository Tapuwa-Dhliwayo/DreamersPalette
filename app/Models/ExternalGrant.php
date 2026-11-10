<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalGrant extends Model
{
    public const TYPES = ['world', 'manuscript', 'collection', 'poem'];

    protected $table = 'account_external_grants';

    protected $fillable = ['resource_type', 'resource_id', 'includes_workspace'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'resource_id' => 'integer', 'includes_workspace' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
