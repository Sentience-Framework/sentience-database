<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\JoinEnum;

class Join
{
    public string|array|Raw $joinTable;
    public ?string $joinTableAlias = null;

    public function __construct(
        public JoinEnum $join,
        string|array|Alias|Raw $joinTable,
        public string $joinTableColumn,
        public string|array|Raw $onTable,
        public string $onTableColumn
    ) {
        if ($joinTable instanceof Alias) {
            $this->joinTable = $joinTable->identifier;
            $this->joinTableAlias = $joinTable->alias;
        } else {
            $this->joinTable = $joinTable;
        }
    }
}
