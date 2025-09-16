<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\QueryWithParamsObject;
use Sentience\Database\Queries\Traits\ReturningTrait;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\Database\Results\ResultsInterface;

class DeleteQuery extends ResultsQueryAbstract
{
    use ReturningTrait;
    use WhereTrait;

    public function toQueryWithParams(): QueryWithParamsObject
    {
        return $this->dialect->delete([
            DialectInterface::CONFIG_TABLE => $this->table,
            DialectInterface::CONFIG_WHERE => $this->where,
            DialectInterface::CONFIG_RETURNING => $this->returning
        ]);
    }

    public function toRawQuery(): string
    {
        return parent::toRawQuery();
    }

    public function execute(): ResultsInterface
    {
        return parent::execute();
    }
}
