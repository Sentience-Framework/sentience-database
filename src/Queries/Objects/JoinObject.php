<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\JoinEnum;

class JoinObject
{
    public string|array|Raw $joinTable;
    public ?string $joinTableAlias = null;

    public function __construct(
        public JoinEnum $join,
        string|array|AliasObject|RawObject $joinTable,
        public string $joinTableColumn,
        public string|array|RawObject $onTable,
        public string $onTableColumn
    ) {
        if ($joinTable instanceof AliasObject) {
            $this->joinTable = $joinTable->name;
            $this->joinTableAlias = $joinTable->alias;
        } else {
            $this->joinTable = $joinTable;
        }
    }
}
