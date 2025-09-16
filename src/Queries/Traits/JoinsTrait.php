<?php

namespace Sentience\Database\Queries\Traits;

use Sentience\Database\Queries\Enums\JoinEnum;
use Sentience\Database\Queries\Objects\AliasObject;
use Sentience\Database\Queries\Objects\JoinObject;
use Sentience\Database\Queries\Objects\RawObject;
use Sentience\Database\Queries\Query;

trait JoinsTrait
{
    protected array $joins = [];

    public function leftJoin(string|array|AliasObject|RawObject $joinTable, string $joinTableColumn, string|array|RawObject $onTable, string $onTableColumn): static
    {
        $this->addJoin(JoinEnum::LEFT_JOIN, $joinTable, $joinTableColumn, $onTable, $onTableColumn);

        return $this;
    }

    public function innerJoin(string|array|AliasObject|RawObject $joinTable, string $joinTableColumn, string|array|RawObject $onTable, string $onTableColumn): static
    {
        $this->addJoin(JoinEnum::INNER_JOIN, $joinTable, $joinTableColumn, $onTable, $onTableColumn);

        return $this;
    }

    public function join(string $expression): static
    {
        $this->joins[] = Query::raw($expression);

        return $this;
    }

    protected function addJoin(JoinEnum $type, string|array|AliasObject|RawObject $joinTable, string $joinTableColumn, string|array|RawObject $onTable, string $onTableColumn): void
    {
        $this->joins[] = new JoinObject($type, $joinTable, $joinTableColumn, $onTable, $onTableColumn);
    }
}
