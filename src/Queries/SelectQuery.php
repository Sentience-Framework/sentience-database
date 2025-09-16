<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\QueryWithParamsObject;
use Sentience\Database\Queries\Objects\RawObject;
use Sentience\Database\Queries\Traits\ColumnsTrait;
use Sentience\Database\Queries\Traits\DistinctTrait;
use Sentience\Database\Queries\Traits\GroupByTrait;
use Sentience\Database\Queries\Traits\HavingTrait;
use Sentience\Database\Queries\Traits\JoinsTrait;
use Sentience\Database\Queries\Traits\LimitTrait;
use Sentience\Database\Queries\Traits\OffsetTrait;
use Sentience\Database\Queries\Traits\OrderByTrait;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\Database\Results\ResultsInterface;

class SelectQuery extends ResultsQueryAbstract
{
    use ColumnsTrait;
    use DistinctTrait;
    use GroupByTrait;
    use HavingTrait;
    use JoinsTrait;
    use LimitTrait;
    use OffsetTrait;
    use OrderByTrait;
    use WhereTrait;

    public function toQueryWithParams(): QueryWithParamsObject
    {
        return $this->dialect->select([
            DialectInterface::CONFIG_DISTINCT => $this->distinct,
            DialectInterface::CONFIG_COLUMNS => $this->columns,
            DialectInterface::CONFIG_TABLE => $this->table,
            DialectInterface::CONFIG_JOINS => $this->joins,
            DialectInterface::CONFIG_WHERE => $this->where,
            DialectInterface::CONFIG_GROUP_BY => $this->groupBy,
            DialectInterface::CONFIG_HAVING => $this->having,
            DialectInterface::CONFIG_ORDER_BY => $this->orderBy,
            DialectInterface::CONFIG_LIMIT => $this->limit,
            DialectInterface::CONFIG_OFFSET => $this->offset
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

    public function count(null|string|array|RawObject $column = null): int
    {
        $previousDistinct = $this->distinct;
        $previousColumns = $this->columns;
        $previousOrderBy = $this->orderBy;

        $this->distinct = false;
        $this->columns = [
            Query::alias(
                Query::raw(
                    sprintf(
                        'COUNT(%s)',
                        !is_null($column)
                        ? ($previousDistinct ? 'DISTINCT ' : '') . $this->dialect->escapeIdentifier($column)
                        : '*'
                    )
                ),
                'count'
            )
        ];
        $this->orderBy = [];

        $count = (int) $this->execute()->fetchObject()?->count ?? 0;

        $this->distinct = $previousDistinct;
        $this->columns = $previousColumns;
        $this->orderBy = $previousOrderBy;

        return $count;
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }
}
