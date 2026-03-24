<?php

declare(strict_types=1);

namespace Cds\NetteModelGenerator\Data;

use Closure;

readonly class CustomType
{
    /**
     * @param list<string>                $annotations
     * @param Closure(Column):string|null $castValueCallback
     */
    public function __construct(
        public string $dbType,
        public string $phpType,
        public array $annotations = [],
        public Closure|null $castValueCallback = null,
    ) {
    }
}
