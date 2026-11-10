<?php

namespace App\Services\Exchange;

use RuntimeException;

/** A missing record and a record the author has not shared are deliberately indistinguishable. */
class ExternalMaterialUnavailable extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('That material is not available to this connection.');
    }
}
