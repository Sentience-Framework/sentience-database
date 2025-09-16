<?php

namespace Sentience\Database\Queries\Traits;

trait RelationsTrait
{
    protected array $relations = [];

    public function relation(string $relation): static
    {
        $this->relation[] = $relation;

        return $this;
    }
}
