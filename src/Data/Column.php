<?php

declare(strict_types=1);

namespace Cds\NetteModelGenerator\Data;

final readonly class Column
{
    public function __construct(
        public Table $table,
        public string $name,
        public string $type,
        public bool $primary,
        public bool $nullable,
        public bool $autoIncrement,
        public ?int $size = null,
        public ?string $comment = null
    ) {
    }
}
