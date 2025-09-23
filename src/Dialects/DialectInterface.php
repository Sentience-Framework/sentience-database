<?php

namespace Sentience\Database\Dialects;

use DateTime;
use DateTimeInterface;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;

interface DialectInterface
{
    public function select(array $config): QueryWithParams;
    public function insert(array $config): QueryWithParams;
    public function update(array $config): QueryWithParams;
    public function delete(array $config): QueryWithParams;
    public function createTable(array $config): QueryWithParams;
    public function alterTable(array $config): array;
    public function dropTable(array $config): QueryWithParams;
    public function escapeIdentifier(string|array|Raw $identifier): string;
    public function escapeString(string $string): string;
    public function castToDriver(mixed $value): mixed;
    public function castToQuery(mixed $value): mixed;
    public function castBool(bool $bool): mixed;
    public function castDateTime(DateTimeInterface $dateTime): mixed;
    public function parseBool(mixed $bool): bool;
    public function parseDateTime(string $string): ?DateTime;
}
