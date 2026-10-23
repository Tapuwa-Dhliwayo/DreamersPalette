<?php

namespace App\Models\Concerns;

trait HasPreciseTimestamps
{
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s.u';
    }
}
