<?php

namespace Sentience\Database\Queries\Traits;

use DateTimeInterface;
use Sentience\Database\Queries\Objects\QueryWithParams;

trait HavingTrait
{
    protected ?QueryWithParams $having = null;

    public function having(string $conditions, bool|int|float|string|DateTimeInterface ...$values): static
    {
        $this->having = new QueryWithParams($conditions, $values);

        return $this;
    }
}
