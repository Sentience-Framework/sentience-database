<?php

namespace Sentience\Database\Queries\Traits;

use DateTimeInterface;
use Sentience\Database\Queries\Objects\QueryWithParamsObject;

trait HavingTrait
{
    protected ?QueryWithParamsObject $having = null;

    public function having(string $expression, bool|int|float|string|DateTimeInterface ...$values): static
    {
        $this->having = new QueryWithParamsObject($expression, $values);

        return $this;
    }
}
