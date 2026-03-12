<?php

declare(strict_types=1);

namespace Cds\NetteModelGenerator;

use Cds\NetteModelGenerator\Generators\ColumnsGenerator;
use Cds\NetteModelGenerator\Generators\DatabaseConventionsGenerator;
use Cds\NetteModelGenerator\Generators\EnumsGenerator;
use Cds\NetteModelGenerator\Generators\ExplorerGenerator;
use Cds\NetteModelGenerator\Generators\Generator;
use Cds\NetteModelGenerator\Generators\ManagerGenerator;
use Cds\NetteModelGenerator\Generators\TablesGenerator;

final class ModelGenerator
{
    /**
     * @var Generator[]
     */
    private array $generators = [];

    public function addGenerator(Generator $generator): void
    {
        $this->generators[] = $generator;
    }

    /**
     * @return \Generator<string>
     */
    public function run(): \Generator
    {
        foreach ($this->generators as $generator) {
            yield from $generator->generate();
        }
    }

    /**
     * @return \Generator<string>
     */
    public function runDefault(GeneratorContext $context): \Generator
    {
        $this->addGenerator(new ExplorerGenerator($context));
        $this->addGenerator(new TablesGenerator($context));
        $this->addGenerator(new ColumnsGenerator($context));
        $this->addGenerator(new EnumsGenerator($context));
        $this->addGenerator(new ManagerGenerator($context));
        $this->addGenerator(new DatabaseConventionsGenerator($context));

        return $this->run();
    }
}
