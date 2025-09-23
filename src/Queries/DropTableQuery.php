<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\IfExistsTrait;
use Sentience\Database\Results\ResultInterface;

class DropTableQuery extends Query
{
    use IfExistsTrait;

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->dropTable([
            'ifExists' => $this->ifExists,
            'table' => $this->table
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
