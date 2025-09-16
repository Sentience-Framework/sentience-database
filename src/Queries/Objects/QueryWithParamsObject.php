<?php

namespace Sentience\Database\Queries\Objects;

use BackedEnum;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Exceptions\QueryWithParamsException;

class QueryWithParamsObject
{
    public array $params;

    public function __construct(public string $query, array $params = [])
    {
        $this->params = array_map(
            fn (mixed $param): mixed => $param instanceof BackedEnum ? $param->value : $param,
            $params
        );
    }

    public function toRawQuery(DialectInterface $dialect): string
    {
        if (count($this->params) == 0) {
            return $this->query;
        }

        $params = array_map(
            fn (mixed $param): mixed => $dialect->castToQuery($param),
            $this->params
        );

        $index = 0;

        return preg_replace_callback(
            '/\?(?=(?:[^\'\"\`\\\\]|\'(?:\\\\.|[^\\\\\'])*\'|\"(?:\\\\.|[^\\\\\"])*\"|\`(?:\\\\.|[^\\\\\`])*\`)*$)/',
            function () use ($params, &$index): mixed {
                if (!array_key_exists($index, $params)) {
                    throw new QueryWithParamsException('placeholder and value count do not match');
                }

                $param = $params[$index];

                if (is_array($param)) {
                    print_r($param);
                    exit;
                }

                $index++;

                return $param;
            },
            $this->query
        );
    }
}
