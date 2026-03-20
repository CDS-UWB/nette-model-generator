<?php

namespace Cds\NetteModelGenerator\Generators;

use Cds\NetteModelGenerator\Data\Column;
use Cds\NetteModelGenerator\Data\Table;

class ColumnsGenerator extends Generator
{
    public function generate(): \Generator
    {
        $this->log('Generating columns:');
        foreach ($this->context->reflection->getTables() as $table) {
            $this->log("\tTable '{$table->name}':");

            yield from $this->generateColumnsForTable($table);
        }
    }

    /**
     * @return array<string>
     */
    public function generateColumnsForTable(Table $table): array
    {
        $name = $this->context->fileManager->getColumnsName($table);
        $filePath = $this->context->fileManager->getColumnsPath($table);

        $this->log("\t\t- {$name}");

        $file = $this->createGeneratedPhpFile();
        $class = $file->addClass($name);

        $columns = iterator_to_array($this->context->reflection->getColumns($table));

        foreach ($columns as $column) {
            $const = $class->addConstant(
                name: $this->sanitizeVariable($column->name, isConstOrEnum: true),
                value: $column->name
            );

            if ($column->comment !== null) {
                $const->setComment($column->comment);
            }
        }

        $class->addMethod('getColumns')
            ->setStatic()
            ->setReturnType('array')
            ->addBody('return ?;', [array_map(static fn (Column $column) => $column->name, $columns)])
            ->addComment("Returns an array of column names.\n")
            ->addComment('@return list<string>')
        ;

        if ($this->writeFile($filePath, $file)) {
            return [$filePath];
        }

        return [];
    }
}
