<?php

namespace Sentience\Database\Queries\Objects;

class DropConstraintObject
{
    public function __construct(public string $constraint)
    {
    }
}
