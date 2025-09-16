<?php

namespace Sentience\Database\Queries\Objects;

class AlterColumnObject
{
    public function __construct(public string $column, public string $options)
    {
    }
}
