<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Enums\OperatorEnum;

class ConditionObject
{
    public function __construct(public OperatorEnum $type, public string|array $expression, public mixed $value, public ChainEnum $chain)
    {
    }
}
