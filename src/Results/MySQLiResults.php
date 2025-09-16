<?php

namespace Sentience\Database\Results;

use mysqli_result;

class MySQLiResults implements ResultsInterface
{
    public function __construct(protected bool|mysqli_result $mysqliResults)
    {
    }

    public function getColumns(): array
    {
        if (!$this->mysqliResults) {
            return [];
        }

        $columns = [];

        $index = 0;

        while (true) {
            $column = $this->mysqliResults->fetch_column($index);

            if (!$column) {
                break;
            }

            $index++;

            $columns[] = $column;
        }

        return $columns;
    }

    public function fetchObject(string $class = 'stdClass'): ?object
    {
        if (!$this->mysqliResults) {
            return null;
        }

        $object = $this->mysqliResults->fetch_object($class);

        if (is_bool($object)) {
            return null;
        }

        return $object;
    }

    public function fetchObjects(string $class = 'stdClass'): array
    {
        if (!$this->mysqliResults) {
            return [];
        }

        $assocs = $this->mysqliResults->fetch_all(MYSQLI_ASSOC);

        return array_map(
            function (array $assoc) use ($class): object {
                $object = new $class();

                foreach ($assoc as $key => $value) {
                    $object->{$key} = $value;
                }

                return $object;
            },
            $assocs
        );
    }

    public function fetchAssoc(): ?array
    {
        if (!$this->mysqliResults) {
            return null;
        }

        $assoc = $this->mysqliResults->fetch_assoc();

        if (is_bool($assoc)) {
            return null;
        }

        return $assoc;
    }

    public function fetchAssocs(): array
    {
        if (!$this->mysqliResults) {
            return [];
        }

        return $this->mysqliResults->fetch_all(MYSQLI_ASSOC);
    }
}
