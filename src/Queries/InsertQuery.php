<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\OnConflictTrait;
use Sentience\Database\Queries\Traits\ReturningTrait;
use Sentience\Database\Queries\Traits\ValuesTrait;
use Sentience\Database\Results\ResultInterface;

class InsertQuery extends Query
{
    use OnConflictTrait;
    use ReturningTrait;
    use ValuesTrait;

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->insert([
            'table' => $this->table,
            'values' => $this->values,
            'onConflict' => $this->onConflict,
            'returning' => $this->returning
        ]);
    }

    public function toRawQuery(): string
    {
        return parent::toRawQuery();
    }

    public function execute(): ResultInterface
    {
        return parent::execute();
    }
}
