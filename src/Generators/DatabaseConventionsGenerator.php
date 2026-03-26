<?php

declare(strict_types=1);

namespace Cds\NetteModelGenerator\Generators;

use Cds\NetteModelGenerator\Data\Column;
use Cds\NetteModelGenerator\Data\Table;
use Cds\NetteModelGenerator\Enum\PhpVersion;
use Iterator;
use Nette\Database\Conventions\DiscoveredConventions;
use Nette\PhpGenerator\Parameter;

class DatabaseConventionsGenerator extends Generator
{
    public function generate(): \Generator
    {
        $this->log('Generating database conventions:');

        $tables = $this->context->reflection->getTables();

        yield from $this->generateTableConventions($tables);
    }

    /**
     * @param Iterator<int,Table> $tables
     *
     * @return array<string> list of changed files
     */
    private function generateTableConventions(Iterator $tables): array
    {
        $mapping = [];

        foreach ($tables as $table) {
            if (!$table->isView) {
                continue;
            }

            $columnNames = array_map(
                callback: static fn (Column $column) => $column->name,
                array: iterator_to_array($this->context->reflection->getColumns($table))
            );

            if (empty($columnNames)) {
                continue;
            }

            if (in_array('id', $columnNames)) {
                $mapping[$table->getFullName()] = 'id';

                continue;
            }

            $mapping[$table->getFullName()] = array_shift($columnNames);
        }

        $className = $this->context->fileManager->getDatabaseConventionsName();
        $filePath = $this->context->fileManager->getDatabaseConventionsPath();
        $file = $this->createGeneratedPhpFile();

        $class = $file->addClass($className);
        $class->setExtends(DiscoveredConventions::class);
        $class->getNamespace()?->addUse(DiscoveredConventions::class);

        if (!empty($mapping)) {
            $const = $class->addConstant('PrimaryKeys', $mapping)
                ->setComment('@var array<string, string>')
                ->setVisibility('protected')
            ;
            if ($this->context->targetPhpVersion->isFeatureSupported(PhpVersion::PHP_83)) {
                $const->setType('array');
            }

            $class->addMethod('getPrimary')
                ->setPublic()
                ->setParameters([(new Parameter('table'))->setType('string')])
                ->setReturnType('string|array|null')
                ->addBody(
                    <<<'PHP'
                    return self::PrimaryKeys[$table] ?? parent::getPrimary($table);
                PHP
                )->addComment('@return string|string[]|null')
            ;
        }

        if ($this->writeFile($filePath, $file)) {
            return [$filePath];
        }

        return [];
    }
}
