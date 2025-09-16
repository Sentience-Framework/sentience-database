<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\ColumnObject;
use Sentience\Database\Queries\Objects\QueryWithParamsObject;
use Sentience\Database\Queries\Traits\ConstraintsTrait;
use Sentience\Database\Queries\Traits\IfNotExistsTrait;
use Sentience\Database\Results\ResultsInterface;

class CreateTableQuery extends ResultsQueryAbstract
{
    use ConstraintsTrait;
    use IfNotExistsTrait;

    protected array $columns = [];
    protected array $primaryKeys = [];

    public function toQueryWithParams(): QueryWithParamsObject
    {
        return $this->dialect->createTable([
            DialectInterface::CONFIG_IF_NOT_EXISTS => $this->ifNotExists,
            DialectInterface::CONFIG_TABLE => $this->table,
            DialectInterface::CONFIG_COLUMNS => $this->columns,
            DialectInterface::CONFIG_PRIMARY_KEYS => $this->primaryKeys,
            DialectInterface::CONFIG_CONSTRAINTS => [
                DialectInterface::CONFIG_CONSTRAINTS_UNIQUE => $this->uniqueConstraints,
                DialectInterface::CONFIG_CONSTRAINTS_FOREIGN_KEY => $this->foreignKeyConstraints
            ]
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

    public function column(string $name, string $type, bool $notNull = false, mixed $defaultValue = null, bool $autoIncrement = false): static
    {
        $this->columns[] = new ColumnObject($name, $type, $notNull, $defaultValue, $autoIncrement);

        return $this;
    }

    public function primaryKeys(string|array $keys): static
    {
        $this->primaryKeys = is_string($keys)
            ? [$keys]
            : $keys;

        return $this;
    }
}
