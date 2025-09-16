<?php

namespace Sentience\Database\Adapters;

use Closure;
use Sentience\Database\Driver;
use Sentience\Database\Helpers\Strings;

abstract class AdapterAbstract implements AdapterInterface
{
    public const string REGEXP_FUNCTION = 'REGEXP';
    public const int REGEXP_FUNCTION_ARGUMENTS_COUNT = 2;

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
    }

    protected function regexpFunction(string $pattern, string $value): bool
    {
        return preg_match("/$pattern/u", $value);
    }

    protected function debug(string $query, float $startTime, ?string $error = null): void
    {
        if (!$this->debug) {
            return;
        }

        ($this->debug)($query, $startTime, $error);
    }
}
