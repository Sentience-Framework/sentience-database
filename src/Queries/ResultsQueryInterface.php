<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParamsObject;

interface ResultsQueryInterface
{
    public function toQueryWithParams(): array|QueryWithParamsObject;
    public function toRawQuery(): string|array;
    public function execute(): mixed;
}
