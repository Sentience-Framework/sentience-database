<?php

namespace Sentience\Database\Adapters;

use Closure;
use mysqli;
use mysqli_sql_exception;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Objects\QueryWithParamsObject;
use Sentience\Database\Results\MySQLiResults;

class MySQLiAdapter extends AdapterAbstract
{
    protected mysqli $mysqli;
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
        $this->mysqli = new mysqli(
            $host,
            $username,
            $password,
            $name,
            $port
        );

        if ($this->mysqli->connect_error) {
            throw new mysqli_sql_exception($this->mysqli->connect_error);
        }

        foreach ($queries as $query) {
            $this->query($query);
        }
    }

    public function query(string $query): void
    {
        $startTime = microtime(true);

        $result = $this->mysqli->query($query);

        if (is_bool($result)) {
            $error = $this->mysqli->error;

            $this->debug($query, $startTime, $error);

            throw new mysqli_sql_exception($error);
        }

        $this->debug($query, $startTime);
    }

    public function queryWithParams(DialectInterface $dialect, QueryWithParamsObject $queryWithParams): MySQLiResults
    {
        $rawQuery = $queryWithParams->toRawQuery($dialect);

        $startTime = microtime(true);

        $mysqliStatement = $this->mysqli->prepare($queryWithParams->query);

        if (is_bool($mysqliStatement)) {
            $error = $this->mysqli->error;

            $this->debug($rawQuery, $startTime, $error);

            throw new mysqli_sql_exception($error);
        }

        $paramTypes = [];
        $params = [];

        foreach ($queryWithParams->params as $param) {
            $value = $dialect->castToDriver($param);

            $paramTypes[] = match (get_debug_type($value)) {
                'null' => 's',
                'int' => 'i',
                'float' => 'd',
                'string' => 's',
                default => 's'
            };

            $params[] = $value;
        }

        $mysqliStatement->bind_param(
            implode(
                '',
                $paramTypes
            ),
            ...$params
        );

        $success = $mysqliStatement->execute();

        if (!$success) {
            $error = $mysqliStatement->error;

            $this->debug($rawQuery, $startTime, $error);

            throw new mysqli_sql_exception($error);
        }

        $results = $mysqliStatement->get_result();

        if (!$results && $mysqliStatement->error) {
            $error = $mysqliStatement->error;

            $this->debug($rawQuery, $startTime, $error);

            throw new mysqli_sql_exception($error);
        }

        $this->debug($rawQuery, $startTime);

        return new MySQLiResults($results);
    }

    public function beginTransaction(): void
    {
        if ($this->inTransaction()) {
            return;
        }

        if (!$this->mysqli->begin_transaction()) {
            throw new mysqli_sql_exception($this->mysqli->error);
        }

        $this->inTransaction = true;
    }

    public function commitTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        if (!$this->mysqli->commit()) {
            throw new mysqli_sql_exception($this->mysqli->error);
        }

        $this->inTransaction = false;
    }

    public function rollbackTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        if (!$this->mysqli->rollback()) {
            throw new mysqli_sql_exception($this->mysqli->error);
        }

        $this->inTransaction = false;
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function lastInsertId(?string $name = null): string
    {
        return (string) $this->mysqli->insert_id;
    }
}
