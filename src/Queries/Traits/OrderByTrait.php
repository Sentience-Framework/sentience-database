<?php

namespace Sentience\Database\Queries\Traits;

use Sentience\Database\Queries\Enums\OrderByDirectionEnum;
use Sentience\Database\Queries\Objects\OrderByObject;
use Sentience\Database\Queries\Objects\RawObject;

trait OrderByTrait
{
    protected array $orderBy = [];

    public function orderByAsc(string|array|RawObject $column): static
    {
        $this->addOrderBy($column, OrderByDirectionEnum::ASC);

        return $this;
    }

    public function orderByDesc(string|array|RawObject $column): static
    {
        $this->addOrderBy($column, OrderByDirectionEnum::DESC);

        return $this;
    }

    protected function addOrderBy(string|array|RawObject $column, OrderByDirectionEnum $direction): void
    {
        $this->orderBy[] = new OrderByObject($column, $direction);
    }
}
