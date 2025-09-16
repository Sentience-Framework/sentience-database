<?php

namespace Sentience\Database\Queries\Objects;

class UniqueConstraintObject
{
    public function __construct(public array $columns, public ?string $name = null)
    {
    }
}
