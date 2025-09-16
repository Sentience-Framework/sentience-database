<?php

namespace Sentience\Database\Queries\Objects;

class RawObject
{
    public function __construct(public string $expression)
    {
    }
}
