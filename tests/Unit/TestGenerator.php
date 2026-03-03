<?php

declare(strict_types=1);

namespace Tests\Unit;

/**
 * Simple test generator implementation.
 *
 * @internal
 */
class TestGenerator extends \Cds\NetteModelGenerator\Generators\Generator
{
    /**
     * @var array<string>
     */
    private array $outputs;

    /**
     * @param array<string> $outputs
     */
    public function __construct(array $outputs)
    {
        $this->outputs = $outputs;
    }

    public function generate(): \Generator
    {
        foreach ($this->outputs as $output) {
            yield $output;
        }
    }
}
