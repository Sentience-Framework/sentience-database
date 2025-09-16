<?php

namespace Sentience\Database\Dialects;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Enums\OperatorEnum;
use Sentience\Database\Queries\Objects\AddColumnObject;
use Sentience\Database\Queries\Objects\AddForeignKeyConstraintObject;
use Sentience\Database\Queries\Objects\AddPrimaryKeysObject;
use Sentience\Database\Queries\Objects\AddUniqueConstraintObject;
use Sentience\Database\Queries\Objects\AliasObject;
use Sentience\Database\Queries\Objects\AlterColumnObject;
use Sentience\Database\Queries\Objects\ColumnObject;
use Sentience\Database\Queries\Objects\ConditionGroupObject;
use Sentience\Database\Queries\Objects\ConditionObject;
use Sentience\Database\Queries\Objects\DropColumnObject;
use Sentience\Database\Queries\Objects\DropConstraintObject;
use Sentience\Database\Queries\Objects\ForeignKeyConstraintObject;
use Sentience\Database\Queries\Objects\OrderByObject;
use Sentience\Database\Queries\Objects\QueryWithParamsObject;
use Sentience\Database\Queries\Objects\RawObject;
use Sentience\Database\Queries\Objects\RenameColumnObject;
use Sentience\Database\Queries\Objects\UniqueConstraintObject;

class SQLDialect implements DialectInterface
{
    public const string IDENTIFIER_ESCAPE = '"';
    public const string STRING_ESCAPE = "'";
    public const bool ANSI_ESCAPE = true;
    public const string DATETIME_FORMAT = 'Y-m-d H:i:s.u';
    public const string REGEX_FUNCTION = 'REGEXP';
    public const string NOT_REGEX_FUNCTION = 'NOT REGEXP';

    public function select(array $config): QueryWithParamsObject
    {
        if (!$config[static::CONFIG_TABLE]) {
            throw new QueryException('no table specified');
        }

        $query = '';
        $params = [];

        $query .= 'SELECT';

        if ($config[static::CONFIG_DISTINCT]) {
            $query .= ' DISTINCT';
        }

        $query .= ' ';
        $query .= count($config[static::CONFIG_COLUMNS]) > 0
            ? implode(
                ', ',
                array_map(
                    function (string|array|AliasObject|RawObject $column): string {
                        if (is_array($column)) {
                            return $this->escapeIdentifier($column);
                        }

                        if ($column instanceof AliasObject) {
                            return $this->escapeIdentifierWithAlias($column->name, $column->alias);
                        }

                        if ($column instanceof RawObject) {
                            return $column->expression;
                        }

                        return $this->escapeIdentifier($column);
                    },
                    $config[static::CONFIG_COLUMNS]
                )
            )
            : '*';

        $query .= ' FROM';

        $this->addTable($query, $config[static::CONFIG_TABLE]);
        $this->addJoins($query, $config[static::CONFIG_JOINS]);
        $this->addWhere($query, $params, $config[static::CONFIG_WHERE]);
        $this->addGroupBy($query, $config[static::CONFIG_GROUP_BY]);
        $this->addHaving($query, $params, $config[static::CONFIG_HAVING]);
        $this->addOrderBy($query, $config[static::CONFIG_ORDER_BY]);
        $this->addLimit($query, $config[static::CONFIG_LIMIT]);
        $this->addOffset($query, $config[static::CONFIG_LIMIT], $config[static::CONFIG_OFFSET]);

        $query .= ';';

        return new QueryWithParamsObject($query, $params);
    }

    public function insert(array $config): QueryWithParamsObject
    {
        if (!$config[static::CONFIG_TABLE]) {
            throw new QueryException('no table specified');
        }

        if (count($config[static::CONFIG_VALUES]) == 0) {
            throw new QueryException('no insert values specified');
        }

        $query = '';
        $params = [];

        $query .= 'INSERT INTO';

        $this->addTable($query, $config[static::CONFIG_TABLE]);

        $query .= sprintf(
            ' (%s)',
            implode(
                ', ',
                array_map(
                    function (string|array|AliasObject|RawObject $column): string {
                        if ($column instanceof RawObject) {
                            return $column->expression;
                        }

                        if ($column instanceof AliasObject) {
                            return $this->escapeIdentifier($column->name);
                        }

                        return $this->escapeIdentifier($column);
                    },
                    array_keys($config[static::CONFIG_VALUES])
                )
            )
        );

        $query .= sprintf(
            ' VALUES (%s)',
            implode(
                ', ',
                array_map(
                    function (mixed $value) use (&$params): string {
                        if ($value instanceof RawObject) {
                            return $value->expression;
                        }

                        $params[] = $value;

                        return '?';
                    },
                    $config[static::CONFIG_VALUES]
                )
            )
        );

        if (array_key_exists('onConflict', $config)) {
            $this->addOnConflict(
                $query,
                $params,
                $config[static::CONFIG_ON_CONFLICT][static::CONFIG_ON_CONFLICT_CONFLICT],
                $config[static::CONFIG_ON_CONFLICT][static::CONFIG_ON_CONFLICT_UPDATES],
                $config[static::CONFIG_ON_CONFLICT][static::CONFIG_ON_CONFLICT_PRIMARY_KEY],
                $config[static::CONFIG_VALUES]
            );
        }

        $this->addReturning($query, $config[static::CONFIG_RETURNING]);

        $query .= ';';

        return new QueryWithParamsObject($query, $params);
    }

    public function update(array $config): QueryWithParamsObject
    {
        if (!$config[static::CONFIG_TABLE]) {
            throw new QueryException('no table specified');
        }

        if (count($config[static::CONFIG_VALUES]) == 0) {
            throw new QueryException('no update values specified');
        }

        $query = '';
        $params = [];

        $query .= 'UPDATE';

        $this->addTable($query, $config[static::CONFIG_TABLE]);

        $query .= ' SET ';
        $query .= implode(
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
                $config[static::CONFIG_VALUES],
                array_keys($config[static::CONFIG_VALUES])
            )
        );

        $this->addWhere($query, $params, $config[static::CONFIG_WHERE]);
        $this->addReturning($query, $config[static::CONFIG_RETURNING]);

        $query .= ';';

        return new QueryWithParamsObject($query, $params);
    }

    public function delete(array $config): QueryWithParamsObject
    {
        if (!$config[static::CONFIG_TABLE]) {
            throw new QueryException('no table specified');
        }

        $query = '';
        $params = [];

        $query .= 'DELETE FROM';

        $this->addTable($query, $config[static::CONFIG_TABLE]);
        $this->addWhere($query, $params, $config[static::CONFIG_WHERE]);
        $this->addReturning($query, $config[static::CONFIG_RETURNING]);

        $query .= ';';

        return new QueryWithParamsObject($query, $params);
    }

    public function createTable(array $config): QueryWithParamsObject
    {
        if (!$config[static::CONFIG_TABLE]) {
            throw new QueryException('no table specified');
        }

        if (count($config[static::CONFIG_COLUMNS]) == 0) {
            throw new QueryException('no table columns specified');
        }

        if (count($config[static::CONFIG_PRIMARY_KEYS]) == 0) {
            throw new QueryException('no table primary key(s) specified');
        }

        $query = '';
        $params = [];

        $query .= 'CREATE TABLE';

        if ($config[static::CONFIG_IF_NOT_EXISTS]) {
            $query .= ' IF NOT EXISTS';
        }

        $this->addTable($query, $config[static::CONFIG_TABLE]);

        $definitions = [];

        foreach ($config[static::CONFIG_COLUMNS] as $column) {
            $definitions[] = $this->stringifyColumnDefinition($column);
        }

        $definitions[] = sprintf(
            'PRIMARY KEY (%s)',
            implode(
                ', ',
                array_map(
                    fn(string|RawObject $column): string => $this->escapeIdentifier($column),
                    $config[static::CONFIG_PRIMARY_KEYS]
                )
            )
        );

        foreach ($config[static::CONFIG_CONSTRAINTS][static::CONFIG_CONSTRAINTS_UNIQUE] as $uniqueConstraint) {
            $definitions[] = $this->stringifyUniqueConstraintDefinition($uniqueConstraint);
        }

        foreach ($config[static::CONFIG_CONSTRAINTS][static::CONFIG_CONSTRAINTS_FOREIGN_KEY] as $foreignKeyConstraint) {
            $definitions[] = $this->stringifyForeignKeyConstraintDefinition($foreignKeyConstraint);
        }

        $query .= sprintf(' (%s)', implode(', ', $definitions));
        $query .= ';';

        return new QueryWithParamsObject($query, $params);
    }

    public function alterTable(array $config): array
    {
        if (!$config[static::CONFIG_TABLE]) {
            throw new QueryException('no table specified');
        }

        if (count($config[static::CONFIG_ALTERS]) == 0) {
            throw new QueryException('no table alters specified');
        }

        return array_map(
            function (object $alter) use ($config): QueryWithParamsObject {
                $query = 'ALTER TABLE';

                $this->addTable($query, $config[static::CONFIG_TABLE]);

                $query .= ' ';

                $query .= match (true) {
                    $alter instanceof AddColumnObject => $this->stringifyAlterTableAddColumn($alter),
                    $alter instanceof AlterColumnObject => $this->stringifyAlterTableAlterColumn($alter),
                    $alter instanceof RenameColumnObject => $this->stringifyAlterTableRenameColumn($alter),
                    $alter instanceof DropColumnObject => $this->stringifyAlterTableDropColumn($alter),
                    $alter instanceof AddPrimaryKeysObject => $this->stringifyAlterTableAddPrimaryKeys($alter),
                    $alter instanceof AddUniqueConstraintObject => $this->stringifyAlterTableAddUniqueConstraint($alter),
                    $alter instanceof AddForeignKeyConstraintObject => $this->stringifyAlterTableAddForeignKeyConstraint($alter),
                    $alter instanceof DropConstraintObject => $this->stringifyAlterTableDropConstraint($alter),
                    default => throw new QueryException('unsupported alter %s', $alter::class)
                };

                $query .= ';';

                return new QueryWithParamsObject($query);
            },
            $config[static::CONFIG_ALTERS]
        );
    }

    public function dropTable(array $config): QueryWithParamsObject
    {
        if (!$config[static::CONFIG_TABLE]) {
            throw new QueryException('no table specified');
        }

        $query = '';
        $params = [];

        $query .= 'DROP TABLE';

        if ($config[static::CONFIG_IF_EXISTS]) {
            $query .= ' IF EXISTS';
        }

        $this->addTable($query, $config[static::CONFIG_TABLE]);

        $query .= ';';

        return new QueryWithParamsObject($query, $params);
    }

    protected function addTable(string &$query, string|array|AliasObject|RawObject $table): void
    {
        $query .= ' ';

        if ($table instanceof AliasObject) {
            $query .= $this->escapeIdentifierWithAlias($table->name, $table->alias);

            return;
        }

        if ($table instanceof RawObject) {
            $query .= $table->expression;

            return;
        }

        $query .= $this->escapeIdentifier($table);
    }

    protected function addJoins(string &$query, array $joins): void
    {
        if (count($joins) == 0) {
            return;
        }

        $query .= ' ';

        foreach ($joins as $index => $join) {
            if ($index > 0) {
                $query .= ' ';
            }

            if ($join instanceof RawObject) {
                $query .= $join->expression;

                continue;
            }

            $query .= sprintf(
                '%s %s ON %s.%s = %s.%s',
                $join->type->value,
                $this->escapeIdentifierWithAlias($join->joinTable, $join->joinTableAlias),
                $this->escapeIdentifier($join->joinTableAlias ?? $join->joinTable),
                $this->escapeIdentifier($join->joinTableColumn),
                $this->escapeIdentifier($join->onTable),
                $this->escapeIdentifier($join->onTableColumn)
            );
        }
    }

    protected function addWhere(string &$query, array &$params, array $where): void
    {
        if (count($where) == 0) {
            return;
        }

        $query .= ' WHERE ';

        foreach ($where as $index => $condition) {
            $condition instanceof ConditionObject
                ? $this->addCondition($query, $params, $index, $condition)
                : $this->addConditionGroup($query, $params, $index, $condition);
        }
    }

    protected function addCondition(string &$query, array &$params, int $index, ConditionObject $condition): void
    {
        if ($index > 0) {
            $query .= sprintf(' %s ', $condition->chain->value);
        }

        if ($condition->type == OperatorEnum::RAW) {
            $query .= sprintf('(%s)', $condition->expression);

            array_push($params, ...$condition->value);

            return;
        }

        if (is_null($condition->value)) {
            $query .= sprintf(
                '%s %s',
                $this->escapeIdentifier($condition->expression),
                $condition->type == OperatorEnum::EQUALS ? 'IS NULL' : 'IS NOT NULL'
            );

            return;
        }

        if (in_array($condition->type, [OperatorEnum::BETWEEN, OperatorEnum::NOT_BETWEEN])) {
            $query .= sprintf(
                '%s %s ? AND ?',
                $this->escapeIdentifier($condition->expression),
                $condition->type->value,
                $condition->value[0],
                $condition->value[1]
            );

            array_push($params, ...$condition->value);

            return;
        }

        if (is_array($condition->value)) {
            if (count($condition->value) == 0) {
                $query .= $condition->type == OperatorEnum::IN ? '1 <> 1' : '1 = 1';

                return;
            }

            $query .= sprintf(
                '%s %s (%s)',
                $this->escapeIdentifier($condition->expression),
                $condition->type->value,
                implode(', ', array_fill(0, count($condition->value), '?'))
            );

            array_push($params, ...$condition->value);

            return;
        }

        if (in_array($condition->type, [OperatorEnum::REGEX, OperatorEnum::NOT_REGEX])) {
            $query .= sprintf(
                '%s %s ?',
                $this->escapeIdentifier($condition->expression),
                $condition->type == OperatorEnum::REGEX ? $this::REGEX_FUNCTION : $this::NOT_REGEX_FUNCTION
            );

            array_push($params, $condition->value);

            return;
        }

        $query .= sprintf(
            '%s %s ?',
            $this->escapeIdentifier($condition->expression),
            $condition->type->value
        );

        array_push($params, $condition->value);
    }

    protected function addConditionGroup(string &$query, array &$params, int $index, ConditionGroupObject $group): void
    {
        if ($index > 0) {
            $query .= sprintf(' %s ', $group->chain->value);
        }

        $conditions = $group->getConditions();

        $query .= '(';

        foreach ($conditions as $index => $condition) {
            $condition instanceof ConditionObject
                ? $this->addCondition($query, $params, $index, $condition)
                : $this->addConditionGroup($query, $params, $index, $condition);
        }

        $query .= ')';
    }

    protected function addGroupBy(string &$query, array $groupBy): void
    {
        if (count($groupBy) == 0) {
            return;
        }

        $query .= sprintf(
            ' GROUP BY %s',
            implode(
                ', ',
                array_map(
                    fn(string|array|RawObject $column): string => $this->escapeIdentifier($column),
                    $groupBy
                )
            )
        );
    }

    protected function addHaving(string &$query, array &$params, ?QueryWithParamsObject $having): void
    {
        if (is_null($having)) {
            return;
        }

        $query .= ' HAVING ' . $having->query;

        array_push($params, ...$having->params);
    }

    protected function addOrderBy(string &$query, array $orderBy): void
    {
        if (count($orderBy) == 0) {
            return;
        }

        $query .= sprintf(
            ' ORDER BY %s',
            implode(
                ', ',
                array_map(
                    fn(OrderByObject $orderBy): string => sprintf(
                        '%s %s',
                        $this->escapeIdentifier($orderBy->column),
                        $orderBy->direction->value
                    ),
                    $orderBy
                )
            )
        );
    }

    protected function addLimit(string &$query, ?int $limit): void
    {
        if (is_null($limit)) {
            return;
        }

        $query .= ' LIMIT ' . $limit;
    }

    protected function addOffset(string &$query, ?int $limit, ?int $offset): void
    {
        if (is_null($limit)) {
            return;
        }

        if (is_null($offset)) {
            return;
        }

        $query .= ' OFFSET ' . $offset;
    }

    protected function addOnConflict(string &$query, array &$params, null|string|array $conflict, ?array $conflictUpdates, ?string $primaryKey, array $insertValues): void
    {
        /**
         * The official SQL standard does not define a clear way to handle conflicts
         */

        return;
    }

    protected function addReturning(string &$query, ?array $returning): void
    {
        if (is_null($returning)) {
            return;
        }

        $columns = empty($returning)
            ? '*'
            : implode(
                ', ',
                array_map(
                    fn(string $column): string => $this->escapeIdentifier($column),
                    $returning
                )
            );

        $query .= ' RETURNING ' . $columns;
    }

    protected function stringifyColumnDefinition(ColumnObject $column): string
    {
        $stringifiedColumn = sprintf(
            '%s %s',
            $this->escapeIdentifier($column->name),
            $column->type
        );

        if ($column->notNull) {
            $stringifiedColumn .= ' NOT NULL';
        }

        if (!is_null($column->defaultValue) && !$column->autoIncrement) {
            $defaultValue = preg_match('/^.*\(.*\)$/', (string) $column->defaultValue)
                ? (string) $column->defaultValue
                : $this->castToQuery($column->defaultValue);

            $stringifiedColumn .= ' DEFAULT ' . $defaultValue;
        }

        return $stringifiedColumn;
    }

    protected function stringifyUniqueConstraintDefinition(UniqueConstraintObject $uniqueConstraint): string
    {
        $stringifiedUniqueConstraint = sprintf(
            'UNIQUE (%s)',
            implode(
                ', ',
                array_map(
                    fn(string $column): string => $this->escapeIdentifier($column),
                    $uniqueConstraint->columns
                )
            )
        );

        if ($uniqueConstraint->name) {
            return sprintf(
                'CONSTRAINT %s %s',
                $this->escapeIdentifier($uniqueConstraint->name),
                $stringifiedUniqueConstraint
            );
        }

        return $stringifiedUniqueConstraint;
    }

    protected function stringifyForeignKeyConstraintDefinition(ForeignKeyConstraintObject $foreignKeyConstraint): string
    {
        $stringifiedForeignKeyConstraint = sprintf(
            'FOREIGN KEY (%s) REFERENCES %s (%s)',
            $foreignKeyConstraint->column,
            $foreignKeyConstraint->referenceTable,
            $foreignKeyConstraint->referenceColumn
        );

        if ($foreignKeyConstraint->name) {
            return sprintf(
                'CONSTRAINT %s %s',
                $this->escapeIdentifier($foreignKeyConstraint->name),
                $stringifiedForeignKeyConstraint
            );
        }

        return $stringifiedForeignKeyConstraint;
    }

    protected function stringifyAlterTableAddColumn(AddColumnObject $addColumn): string
    {
        return sprintf(
            'ADD COLUMN %s',
            $this->stringifyColumnDefinition($addColumn)
        );
    }

    protected function stringifyAlterTableAlterColumn(AlterColumnObject $alterColumn): string
    {
        return sprintf(
            'ALTER COLUMN %s %s',
            $this->escapeIdentifier($alterColumn->column),
            $alterColumn->options
        );
    }

    protected function stringifyAlterTableRenameColumn(RenameColumnObject $renameColumn): string
    {
        return sprintf(
            'RENAME COLUMN %s TO %s',
            $this->escapeIdentifier($renameColumn->oldName),
            $this->escapeIdentifier($renameColumn->newName)
        );
    }

    protected function stringifyAlterTableDropColumn(DropColumnObject $dropColumn): string
    {
        return sprintf(
            'DROP COLUMN %s',
            $this->escapeIdentifier($dropColumn->column)
        );
    }

    protected function stringifyAlterTableAddPrimaryKeys(AddPrimaryKeysObject $addPrimaryKeys): string
    {
        return sprintf(
            'ADD PRIMARY KEY (%s)',
            implode(
                ', ',
                array_map(
                    fn(string|array|RawObject $column): string => $this->escapeIdentifier($column),
                    $addPrimaryKeys->columns
                )
            )
        );
    }

    protected function stringifyAlterTableAddUniqueConstraint(AddUniqueConstraintObject $addUniqueConstraint): string
    {
        return sprintf(
            'ADD %s',
            $this->stringifyUniqueConstraintDefinition($addUniqueConstraint)
        );
    }

    protected function stringifyAlterTableAddForeignKeyConstraint(AddForeignKeyConstraintObject $addForeignKeyConstraint): string
    {
        return sprintf(
            'ADD %s',
            $this->stringifyForeignKeyConstraintDefinition($addForeignKeyConstraint)
        );
    }

    protected function stringifyAlterTableDropConstraint(DropConstraintObject $dropConstraint): string
    {
        return sprintf(
            'DROP CONSTRAINT %s',
            $this->escapeIdentifier($dropConstraint->constraint)
        );
    }

    protected function escapeIdentifierWithAlias(string|array|RawObject $identifier, ?string $alias): string
    {
        $escapedIdentifier = $this->escapeIdentifier($identifier);

        if (!$alias) {
            return $escapedIdentifier;
        }

        return sprintf('%s AS %s', $escapedIdentifier, $this->escapeIdentifier($alias));
    }

    public function escapeIdentifier(string|array|RawObject $identifier): string
    {
        if ($identifier instanceof RawObject) {
            return $identifier->expression;
        }

        return is_array($identifier)
            ? implode(
                '.',
                array_map(
                    fn(string|RawObject $identifier): string => $this->escapeIdentifier($identifier),
                    $identifier
                )
            )
            : $this->escape($identifier, $this::IDENTIFIER_ESCAPE);
    }

    public function escapeString(string $string): string
    {
        return $this->escape($string, $this::STRING_ESCAPE);
    }

    protected function escape(string $string, string $char): string
    {
        $escapedString = $this::ANSI_ESCAPE
            ? $this->escapeAnsi($string, $char)
            : $this->escapeBackslash($string, $char);

        return $char . $escapedString . $char;
    }

    protected function escapeAnsi(string $string, string $char): string
    {
        return preg_replace(
            '/' . preg_quote((string) $char, '/') . '/',
            '$0$0',
            (string) $string
        );
    }

    protected function escapeBackslash(string $string, string $char): string
    {
        $chars = ['\\', $char];

        foreach ($chars as $char) {
            $string = preg_replace(
                sprintf(
                    '/(?<!\\\\)(?:\\\\\\\\)*%s/',
                    preg_quote((string) $char, '/')
                ),
                '\\\$0',
                (string) $string
            );
        }

        return $string;
    }

    public function castToDriver(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $this->castBool($value);
        }

        if ($value instanceof DateTimeInterface) {
            return $this->castDateTime($value);
        }

        return $value;
    }

    public function castToQuery(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->escapeString($value);
        }

        if (is_bool($value)) {
            $bool = $this->castBool($value);

            return is_string($bool)
                ? $this->escapeString($bool)
                : $bool;
        }

        if (is_null($value)) {
            return 'NULL';
        }

        if ($value instanceof DateTimeInterface) {
            return $this->escapeString($this->castDateTime($value));
        }

        return $value;
    }

    public function castBool(bool $bool): mixed
    {
        return $bool ? 1 : 0;
    }

    public function castDateTime(DateTimeInterface $dateTimeInterface): mixed
    {
        return $dateTimeInterface->format($this::DATETIME_FORMAT);
    }

    public function parseBool(mixed $value): bool
    {
        return $value == 1 ? true : false;
    }

    public function parseDateTime(string $string): ?DateTime
    {
        $timestamp = strtotime($string);

        if (!$timestamp) {
            return null;
        }

        $hasMicroseconds = preg_match('/\.([0-9]{0,6})[\+\-]?/', $string, $microsecondMatches);

        $instance = DateTime::createFromFormat(
            'U.u',
            sprintf(
                '%d.%d',
                $timestamp,
                $hasMicroseconds ? (int) $microsecondMatches[1] : 0
            )
        );

        if (!$instance) {
            return null;
        }

        $hasTimezoneOffset = preg_match('/([\+\-])([0-9]+)\:?([0-9]*)$/', $string, $timezoneOffsetMatches);

        if ($hasTimezoneOffset) {
            [$modifier, $timezoneOffsetHours, $timezoneOffsetMinutes] = array_slice($timezoneOffsetMatches, 1);

            $multiplier = ((int) $timezoneOffsetHours + (int) $timezoneOffsetMinutes / 60) * ($modifier == '+' ? 1 : -1);

            $timezone = timezone_name_from_abbr('', (int) ($multiplier * 3600));

            if ($timezone) {
                $instance->setTimezone(new DateTimeZone($timezone));
            }
        }

        return $instance;
    }
}
