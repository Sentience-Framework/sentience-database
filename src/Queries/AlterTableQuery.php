<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\AddColumnObject;
use Sentience\Database\Queries\Objects\AddForeignKeyConstraintObject;
use Sentience\Database\Queries\Objects\AddPrimaryKeysObject;
use Sentience\Database\Queries\Objects\AddUniqueConstraintObject;
use Sentience\Database\Queries\Objects\AlterColumnObject;
use Sentience\Database\Queries\Objects\DropColumnObject;
use Sentience\Database\Queries\Objects\DropConstraintObject;
use Sentience\Database\Queries\Objects\QueryWithParamsObject;
use Sentience\Database\Queries\Objects\RenameColumnObject;
use Sentience\Database\Results\ResultsInterface;

class AlterTableQuery extends ResultsQueryAbstract
{
    protected array $alters = [];

    public function toQueryWithParams(): array
    {
        return $this->dialect->alterTable([
            DialectInterface::CONFIG_TABLE => $this->table,
            DialectInterface::CONFIG_ALTERS => $this->alters
        ]);
    }

    public function toRawQuery(): array
    {
        $queriesWithParams = $this->toQueryWithParams();

        return array_map(
            fn(QueryWithParamsObject $queryWithParams): string => $queryWithParams->toRawQuery($this->dialect),
            $queriesWithParams
        );
    }

    public function execute(): array
    {
        $queries = $this->toQueryWithParams();

        return array_map(
            fn(QueryWithParamsObject $queryWithParams): ResultsInterface => $this->database->queryWithParams($queryWithParams),
            $queries
        );
    }

    public function addColumn(string $name, string $type, bool $notNull = false, mixed $defaultValue = null, bool $autoIncrement = false): static
    {
        $this->alters[] = new AddColumnObject($name, $type, $notNull, $defaultValue, $autoIncrement);

        return $this;
    }

    public function alterColumn(string $column, string $options): static
    {
        $this->alters[] = new AlterColumnObject($column, $options);

        return $this;
    }

    public function renameColumn(string $oldName, string $newName): static
    {
        $this->alters[] = new RenameColumnObject($oldName, $newName);

        return $this;
    }

    public function dropColumn(string $column): static
    {
        $this->alters[] = new DropColumnObject($column);

        return $this;
    }

    public function addPrimaryKeys(string|array $columns): static
    {
        $this->alters[] = new AddPrimaryKeysObject(is_string($columns) ? [$columns] : $columns);

        return $this;
    }

    public function addUniqueConstraint(array $columns, ?string $name): static
    {
        $this->alters[] = new AddUniqueConstraintObject($columns, $name);

        return $this;
    }

    public function addForeignKeyConstraint(string $column, string $referenceTable, string $referenceColumn, ?string $name = null): static
    {
        $this->alters[] = new AddForeignKeyConstraintObject($column, $referenceTable, $referenceColumn, $name);

        return $this;
    }

    public function dropConstraint(string $constraint): static
    {
        $this->alters[] = new DropConstraintObject($constraint);

        return $this;
    }
}
