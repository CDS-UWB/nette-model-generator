<?php

declare(strict_types=1);

namespace Cds\NetteModelGenerator\Data;

final readonly class Enum
{
    /**
     * @param array<string> $values
     */
    public function __construct(
        public string $name,
        public array $values,
        public ?string $schema = null,
    ) {
    }
}
