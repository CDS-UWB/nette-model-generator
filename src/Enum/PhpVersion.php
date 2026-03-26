<?php

declare(strict_types=1);

namespace Cds\NetteModelGenerator\Enum;

enum PhpVersion: int
{
    case PHP_82 = 82;
    case PHP_83 = 83;
    case PHP_84 = 84;

    public function isFeatureSupported(PhpVersion $target): bool
    {
        return $this->value >= $target->value;
    }
}
