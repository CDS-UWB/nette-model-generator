<?php

declare(strict_types=1);

namespace Cds\NetteModelGenerator\Data;

use Closure;

readonly class CustomType
{
    /**
     * @param string                      $phpType           Accepts PHPStan generic types
     *                                                       (e.g. SomeType<TemplateType>). When a generic type is used,
     *                                                       the generated attribute keeps the base type (SomeType)
     *                                                       and a `@var` annotation records the full generic signature.
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

    /**
     * Returns the base PHP type without generic parameters.
     */
    public function getPhpTypeWithoutGeneric(): string
    {
        if (($start = strpos($this->phpType, '<')) !== false) {
            return substr($this->phpType, 0, $start);
        }

        return $this->phpType;
    }
}
