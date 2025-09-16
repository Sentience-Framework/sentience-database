<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\OrderByDirectionEnum;

class OrderByObject
{
    public function __construct(public string|array|RawObject $column, public OrderByDirectionEnum $direction)
    {
    }
}
