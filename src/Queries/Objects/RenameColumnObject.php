<?php

namespace Sentience\Database\Queries\Objects;

class RenameColumnObject
{
    public function __construct(public string $oldName, public string $newName)
    {
    }
}
