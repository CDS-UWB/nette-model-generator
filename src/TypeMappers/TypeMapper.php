<?php

namespace Cds\NetteModelGenerator\TypeMappers;

use Cds\NetteModelGenerator\Reflections\Reflection;
use InvalidArgumentException;

/**
 * @template T of Reflection
 */
abstract readonly class TypeMapper
{
    /** @var array<string, string> */
    public const array BaseTypes = [];

    /**
     * @param T                    $reflection
     * @param array<string, mixed> $customTypes
     */
    public function __construct(protected Reflection $reflection, protected array $customTypes = [])
    {
    }

    public function toPhp(string $type, int|null $size = null): string
    {
        $type = strtolower($type);

        if (str_ends_with($type, '[]')) {
            return 'array';
        }

        if (($item = $this->customConversion($type, $size)) !== null) {
            return $item;
        }

        if (array_key_exists($type, $this->customTypes)) {
            return $this->customTypes[$type];
        }

        if (array_key_exists($type, static::BaseTypes)) {
            return static::BaseTypes[$type];
        }

        throw new InvalidArgumentException("Unknown type: {$type}");
    }

    protected function customConversion(string $type, int|null $size): string|null
    {
        return null;
    }
}
