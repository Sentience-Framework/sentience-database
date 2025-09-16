<?php

namespace Sentience\Database\Adapters;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\QueryWithParamsObject;
use Sentience\Database\Results\ResultsInterface;

interface AdapterInterface
{
    public function query(string $query): void;
    public function queryWithParams(DialectInterface $dialect, QueryWithParamsObject $queryWithParams): ResultsInterface;
    public function beginTransaction(): void;
    public function commitTransaction(): void;
    public function rollbackTransaction(): void;
    public function inTransaction(): bool;
    public function lastInsertId(?string $name = null): string;
}
