<?php

namespace Sentience\Database\Queries\Objects;

class ForeignKeyConstraintObject
{
    public function __construct(public string $column, public string $referenceTable, public string $referenceColumn, public ?string $name = null)
    {
    }
}
