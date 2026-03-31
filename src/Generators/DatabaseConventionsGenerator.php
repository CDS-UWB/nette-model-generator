<?php

declare(strict_types=1);

namespace Cds\NetteModelGenerator\Generators;

use Cds\NetteModelGenerator\Data\Column;
use Cds\NetteModelGenerator\Data\Table;
use Cds\NetteModelGenerator\Enum\PhpVersion;
use Iterator;
use Nette\Database\Conventions\DiscoveredConventions;
use Nette\Database\IStructure;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PromotedParameter;

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
        $mapping = $this->loadPrimaryKeys($tables);

        $className = $this->context->fileManager->getDatabaseConventionsName();
        $filePath = $this->context->fileManager->getDatabaseConventionsPath();
        $file = $this->createGeneratedPhpFile();
        $dbConventionClass = $this->context->dbConventionsClass ?? DiscoveredConventions::class;

        $class = $file->addClass($className);
        $class->setExtends($dbConventionClass);

        $const = $class->addConstant('PrimaryKeys', $mapping)
            ->setComment('@var array<string, string>')
            ->setVisibility('public')
        ;
        if ($this->context->targetPhpVersion->isFeatureSupported(PhpVersion::PHP_83)) {
            $const->setType('array');
        }

        $constructor = $class->addMethod('__construct')
            ->setPublic()
        ;

        // Custom conventions -- merge primary keys with parent class and do not generate methods
        if ($this->context->dbConventionsClass !== null) {
            $constructor->setParameters([
                (new Parameter('structure'))->setType(IStructure::class),
                (new Parameter('primaryKeys'))
                    ->setType('array')
                    ->setDefaultValue(new Literal('[]')),
            ])
                ->setBody(<<<'PHP'
                $keys = array_merge(parent::PrimaryKeys, self::PrimaryKeys);
                $keys = array_merge($keys, $primaryKeys);

                parent::__construct(
                    structure: $structure,
                    primaryKeys: $keys,
                );
                PHP)
                ->addComment('@param array<string, string> $primaryKeys')
            ;

            if ($this->writeFile($filePath, $file)) {
                return [$filePath];
            }

            return [];
        }

        $constructor
            ->setParameters([
                (new Parameter('structure'))->setType(IStructure::class),
                (new PromotedParameter('primaryKeys'))
                    ->setType('array')
                    ->setDefaultValue(new Literal('self::PrimaryKeys'))
                    ->setVisibility('protected')
                    ->setReadOnly(),
            ])
            ->setBody('parent::__construct($structure);')
            ->addComment('@param array<string, string> $primaryKeys')
        ;

        $class->addMethod('getPrimary')
            ->setPublic()
            ->setParameters([(new Parameter('table'))->setType('string')])
            ->setReturnType('string|array|null')
            ->addBody(
                <<<'PHP'
                return $this->primaryKeys[$table] ?? parent::getPrimary($table);
            PHP
            )->addComment('@return string|string[]|null')
        ;

        if ($this->writeFile($filePath, $file)) {
            return [$filePath];
        }

        return [];
    }

    /**
     * @param Iterator<int,Table> $tables
     *
     * @return array<string, string> mapping of table full name to primary key column name
     */
    private function loadPrimaryKeys(Iterator $tables): array
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

        return $mapping;
    }
}
