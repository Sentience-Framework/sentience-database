<?php

namespace Sentience\Database\Queries\Objects;

class AliasObject
{
    public function __construct(public string|array|RawObject $name, public string $alias)
    {
    }
}
