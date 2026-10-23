<?php

namespace App\Models;

use App\Models\Concerns\HasPreciseTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasPreciseTimestamps;

    public const UPDATED_AT = null;

    protected $table = 'publishing_profiles';

    protected $hidden = ['source_uuid', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'];

    protected $fillable = ['display_name', 'bio', 'avatar_url'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'created_at' => 'immutable_datetime', 'two_factor_confirmed_at' => 'immutable_datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
