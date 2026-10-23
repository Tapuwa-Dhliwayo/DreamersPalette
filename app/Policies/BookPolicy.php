<?php

namespace App\Policies;

class BookPolicy extends ContentPolicy
{
    protected string $resource = 'books';
}
