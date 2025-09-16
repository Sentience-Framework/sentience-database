<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\QueryWithParamsObject;
use Sentience\Database\Queries\Traits\IfExistsTrait;
use Sentience\Database\Results\ResultsInterface;

class DropTableQuery extends ResultsQueryAbstract
{
    use IfExistsTrait;

    public function toQueryWithParams(): QueryWithParamsObject
    {
        return $this->dialect->dropTable([
            DialectInterface::CONFIG_IF_EXISTS => $this->ifExists,
            DialectInterface::CONFIG_TABLE => $this->table
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
