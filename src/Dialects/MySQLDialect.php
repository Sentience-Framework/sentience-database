<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\Queries\Objects\AlterColumnObject;
use Sentience\Database\Queries\Objects\ColumnObject;
use Sentience\Database\Queries\Objects\DropConstraintObject;
use Sentience\Database\Queries\Objects\QueryWithParamsObject;
use Sentience\Database\Queries\Query;

class MySQLDialect extends SQLDialect implements DialectInterface
{
    public const string IDENTIFIER_ESCAPE = '`';
    public const string STRING_ESCAPE = '"';
    public const bool ANSI_ESCAPE = false;

    public function createTable(array $config): QueryWithParamsObject
    {
        $queryWithParams = parent::createTable($config);

        $queryWithParams->query = substr_replace(
            $queryWithParams->query,
            ' ENGINE=InnoDB;',
            -1
        );

        return $queryWithParams;
    }

    public function addOnConflict(string &$query, array &$params, null|string|array $conflict, ?array $conflictUpdates, ?string $primaryKey, array $insertValues): void
    {
        if (is_null($conflict)) {
            return;
        }

        if (is_null($conflictUpdates) && !$primaryKey) {
            $query = substr_replace($query, 'INSERT IGNORE', 0, 6);

            return;
        }

        $updates = !empty($conflictUpdates) ? $conflictUpdates : $insertValues;

        if ($primaryKey) {
            $lastInsertId = Query::raw(
                sprintf(
                    'LAST_INSERT_ID(%s)',
                    $this->escapeIdentifier($primaryKey)
                )
            );

            $updates = is_null($conflictUpdates)
                ? [$primaryKey => $lastInsertId]
                : [...$updates, $primaryKey => $lastInsertId];
        }

        $query .= sprintf(
            ' ON DUPLICATE KEY UPDATE %s',
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

    public function addReturning(string &$query, ?array $returning): void
    {
        return;
    }

    public function stringifyColumnDefinition(ColumnObject $column): string
    {
        $stringifiedColumn = parent::stringifyColumnDefinition($column);

        if ($column->autoIncrement && str_contains(strtolower($column->type), 'int')) {
            $stringifiedColumn .= ' AUTO_INCREMENT';
        }

        return $stringifiedColumn;
    }

    public function stringifyAlterTableAlterColumn(AlterColumnObject $alterColumn): string
    {
        $stringifiedAlterColumn = parent::stringifyAlterTableAlterColumn($alterColumn);

        return substr_replace($stringifiedAlterColumn, 'MODIFY', 0, 5);
    }

    public function stringifyAlterTableDropConstraint(DropConstraintObject $dropConstraint): string
    {
        $stringifiedDropConstraint = parent::stringifyAlterTableDropConstraint($dropConstraint);

        return substr_replace($stringifiedDropConstraint, 'INDEX', 5, 10);
    }
}
