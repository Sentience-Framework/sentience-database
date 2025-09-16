<?php

namespace Sentience\Database\Queries\Objects;

class ColumnObject
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $notNull = false,
        public mixed $defaultValue = null,
        public bool $autoIncrement = false
    ) {
    }
}
