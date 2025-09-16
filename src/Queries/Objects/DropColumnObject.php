<?php

namespace Sentience\Database\Queries\Objects;

class DropColumnObject
{
    public function __construct(public string $column)
    {
    }
}
