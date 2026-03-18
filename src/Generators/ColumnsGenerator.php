<?php

namespace Cds\NetteModelGenerator\Generators;

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

        foreach ($this->context->reflection->getColumns($table) as $column) {
            $const = $class->addConstant(
                name: $this->sanitizeVariable($column->name, isConstOrEnum: true),
                value: $column->name
            );

            if ($column->comment !== null) {
                $const->setComment($column->comment);
            }
        }

        if ($this->writeFile($filePath, $file)) {
            return [$filePath];
        }

        return [];
    }
}
