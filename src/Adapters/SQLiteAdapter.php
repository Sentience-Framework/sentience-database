<?php

namespace Sentience\Database\Adapters;

use Closure;
use SQLite3;
use SQLite3Exception;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Objects\QueryWithParamsObject;
use Sentience\Database\Results\SQLiteResults;

class SQLiteAdapter extends AdapterAbstract
{
    public const OPTIONS_READ_ONLY = 'READ_ONLY';
    public const OPTIONS_ENCRYPTION_KEY = 'ENCRYPTION_KEY';
    public const OPTIONS_BUSY_TIMEOUT = 'BUSY_TIMEOUT';

    protected SQLite3 $sqlite;
    protected bool $inTransaction = false;

    public function __construct(
        protected Driver $driver,
        protected string $host,
        protected int $port,
        protected string $name,
        protected string $username,
        protected string $password,
        protected array $queries,
        protected ?Closure $debug,
        protected array $options
    ) {
        $this->sqlite = new SQLite3(
            $name,
            ($options[static::OPTIONS_READ_ONLY] ?? false) ? SQLITE3_OPEN_READONLY : SQLITE3_OPEN_READWRITE,
            $options[static::OPTIONS_ENCRYPTION_KEY] ?? ''
        );

        $this->sqlite->createFunction(
            static::REGEXP_FUNCTION,
            fn(string $pattern, string $value): bool => $this->regexpFunction($pattern, $value),
            static::REGEXP_FUNCTION_ARGUMENTS_COUNT
        );

        foreach ($queries as $query) {
            $this->query($query);
        }
    }

    public function query(string $query): void
    {
        $startTime = microtime(true);

        $success = $this->sqlite->exec($query);

        if (!$success) {
            $error = $this->sqlite->lastErrorMsg();

            $this->debug($query, $startTime, $error);

            throw new SQLite3Exception($error);
        }

        $this->debug($query, $startTime);
    }

    public function queryWithParams(DialectInterface $dialect, QueryWithParamsObject $queryWithParams): SQLiteResults
    {
        $rawQuery = $queryWithParams->toRawQuery($dialect);

        $startTime = microtime(true);

        $sqlite3Statement = $this->sqlite->prepare($queryWithParams->query);

        if (is_bool($sqlite3Statement)) {
            $error = $this->sqlite->lastErrorMsg();

            $this->debug($rawQuery, $startTime, $error);

            throw new SQLite3Exception($error);
        }

        foreach ($queryWithParams->params as $index => $param) {
            $value = $dialect->castToDriver($param);

            $sqlite3Statement->bindValue(
                $index + 1,
                $value,
                match (get_debug_type($value)) {
                    'null' => SQLITE3_NULL,
                    'int' => SQLITE3_INTEGER,
                    'float' => SQLITE3_FLOAT,
                    'string' => SQLITE3_TEXT,
                    default => SQLITE3_TEXT
                }
            );
        }

        $sqlite3Results = $sqlite3Statement->execute();

        if (!$sqlite3Results) {
            $error = $this->sqlite->lastErrorMsg();

            $this->debug($rawQuery, $startTime, $error);

            throw new SQLite3Exception($error);
        }

        $this->debug($rawQuery, $startTime);

        return new SQLiteResults($sqlite3Results);
    }

    public function beginTransaction(): void
    {
        if ($this->inTransaction()) {
            return;
        }

        $this->sqlite->query('BEGIN;');

        $this->inTransaction = true;
    }

    public function commitTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        $this->query('COMMIT;');

        $this->inTransaction = false;
    }

    public function rollbackTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        $this->query('ROLLBACK;');

        $this->inTransaction = false;
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function lastInsertId(?string $name = null): string
    {
        return (string) $this->sqlite->lastInsertRowID();
    }
}
