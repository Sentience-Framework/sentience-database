<?php

namespace Sentience\Database\Dialects;

use DateTime;
use DateTimeImmutable;
use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Objects\AddForeignKeyConstraintObject;
use Sentience\Database\Queries\Objects\AddPrimaryKeysObject;
use Sentience\Database\Queries\Objects\AddUniqueConstraintObject;
use Sentience\Database\Queries\Objects\AlterColumnObject;
use Sentience\Database\Queries\Objects\DropConstraintObject;
use Sentience\Timestamp\Timestamp;

class SQLiteDialect extends SQLDialect implements DialectInterface
{
    public function addOnConflict(string &$query, array &$params, null|string|array $conflict, ?array $conflictUpdates, ?string $primaryKey, array $insertValues): void
    {
        if (is_null($conflict)) {
            return;
        }

        if (is_string($conflict)) {
            throw new QueryException('SQLite does not support named constraints, please use an array of columns');
        }

        $expression = sprintf(
            '(%s)',
            implode(
                ', ',
                array_map(
                    fn(string $column): string => $this->escapeIdentifier($column),
                    $conflict
                )
            )
        );

        if (is_null($conflictUpdates)) {
            $query .= sprintf(' ON CONFLICT %s DO NOTHING', $expression);

            return;
        }

        $updates = !empty($conflictUpdates) ? $conflictUpdates : $insertValues;

        $query .= sprintf(
            ' ON CONFLICT %s DO UPDATE SET %s',
            $expression,
            implode(
                ', ',
                array_map(
                    function (mixed $value, string $key) use (&$params): string {
                        if ($value instanceof RawObject) {
                            return sprintf(
                                '%s = %s',
                                $this->escapeIdentifier($key),
                                $value->expression
                            );
                        }

                        $params[] = $value;

                        return sprintf('%s = ?', $this->escapeIdentifier($key));
                    },
                    $updates,
                    array_keys($updates)
                )
            )
        );
    }

    public function stringifyAlterTableAlterColumn(AlterColumnObject $alterColumn): string
    {
        throw new QueryException('SQLite does not support altering columns');
    }

    protected function stringifyAlterTableAddPrimaryKeys(AddPrimaryKeysObject $addPrimaryKeys): string
    {
        throw new QueryException('SQLite does not support adding primary keys by altering the table');
    }

    protected function stringifyAlterTableAddUniqueConstraint(AddUniqueConstraintObject $addUniqueConstraint): string
    {
        throw new QueryException('SQLite does not support adding constraints by altering the table');
    }

    protected function stringifyAlterTableAddForeignKeyConstraint(AddForeignKeyConstraintObject $addForeignKeyConstraint): string
    {
        throw new QueryException('SQLite does not support adding constraints by altering the table');
    }

    protected function stringifyAlterTableDropConstraint(DropConstraintObject $dropConstraint): string
    {
        throw new QueryException('SQLite does not support dropping constraints by altering the table');
    }
}
