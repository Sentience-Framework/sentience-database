<?php

namespace Sentience\Database\Queries\Traits;

use Sentience\Database\Queries\Objects\ForeignKeyConstraintObject;
use Sentience\Database\Queries\Objects\UniqueConstraintObject;

trait ConstraintsTrait
{
    protected array $uniqueConstraints = [];
    protected array $foreignKeyConstraints = [];

    public function uniqueConstraint(array $columns, ?string $name = null): static
    {
        $this->uniqueConstraints[] = new UniqueConstraintObject($columns, $name);

        return $this;
    }

    public function foreignKeyConstraint(string $column, string $referenceTable, string $referenceColumn, ?string $name = null): static
    {
        $this->foreignKeyConstraints[] = new ForeignKeyConstraintObject($column, $referenceTable, $referenceColumn, $name);

        return $this;
    }
}
