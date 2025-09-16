<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\QueryWithParamsObject;
use Sentience\Database\Queries\Traits\OnConflictTrait;
use Sentience\Database\Queries\Traits\ReturningTrait;
use Sentience\Database\Queries\Traits\ValuesTrait;
use Sentience\Database\Results\ResultsInterface;

class InsertQuery extends ResultsQueryAbstract
{
    use OnConflictTrait;
    use ReturningTrait;
    use ValuesTrait;

    public function toQueryWithParams(): QueryWithParamsObject
    {
        return $this->dialect->insert([
            DialectInterface::CONFIG_TABLE => $this->table,
            DialectInterface::CONFIG_VALUES => $this->values,
            DialectInterface::CONFIG_ON_CONFLICT => [
                DialectInterface::CONFIG_ON_CONFLICT_CONFLICT => $this->onConflict,
                DialectInterface::CONFIG_ON_CONFLICT_UPDATES => $this->onConflictUpdates,
                DialectInterface::CONFIG_ON_CONFLICT_PRIMARY_KEY => $this->onConflictPrimaryKey
            ],
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
