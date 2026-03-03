<?php

declare(strict_types=1);

namespace Cds\NetteModelGenerator\Data;

final readonly class Table
{
    public function __construct(
        public string $name,
        public ?string $schema = null,
    ) {
    }

    public function getFullName(): string
    {
        return $this->schema ? $this->schema . '.' . $this->name : $this->name;
    }
}
