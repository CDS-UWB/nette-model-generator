<?php

namespace Cds\NetteModelGenerator\Generators;

use Cds\NetteModelGenerator\Data\Table;
use Cds\NetteModelGenerator\Utils;

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

        $sanitizeCallback = $this->context->varNameSanitizer ?? Utils::sanitizeVariableName(...);

        foreach ($this->context->reflection->getColumns($table) as $column) {
            $const = $class->addConstant($sanitizeCallback($column->name), $column->name);

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
