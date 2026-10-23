<?php

namespace App\Models;

use App\Models\Concerns\HasPreciseTimestamps;

class Passkey extends \Laravel\Passkeys\Passkey
{
    use HasPreciseTimestamps;

    protected $hidden = ['credential', 'credential_id'];
}
