<?php

namespace Sentience\Database\Queries\Objects;

class Raw
{
    public function __construct(public string $string)
    {
    }

    public function __tostring(): string
    {
        return $this->string;
    }
}
